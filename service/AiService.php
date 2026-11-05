<?php

namespace App\Service;

use Exception;
use App\Config\Logger;

require_once __DIR__ . '/../config/bootstrap.php';

class AiService
{
    private $apiKey;

    private string $apiUrl =
    "https://openrouter.ai/api/v1/chat/completions";

    public function __construct()
    {
        $this->apiKey = OPENROUTER_API_KEY;
    }

    public function parseRecipe(string $textoReceita): array
    {
        $prompt = $this->buildPrompt($textoReceita);

        $ultimaException = null;

        for ($tentativa = 1; $tentativa <= 2; $tentativa++) {

            try {

                Logger::info(
                    'ai/receitas',
                    'Iniciando geração de receita',
                    [
                        'tentativa' => $tentativa
                    ]
                );

                $response = $this->callOpenRouter($prompt);

                Logger::info(
                    'ai/receitas',
                    'Resposta recebida da IA',
                    [
                        'tentativa' => $tentativa,
                        'response' => $response
                    ]
                );

                $receita = $this->extractJson($response);

                Logger::info(
                    'ai/receitas',
                    'Receita gerada com sucesso',
                    [
                        'tentativa' => $tentativa,
                        'titulo' => $receita['titulo'] ?? null
                    ]
                );

                return $receita;
            } catch (Exception $e) {

                $ultimaException = $e;

                Logger::warn(
                    'ai/receitas',
                    'Falha ao processar resposta da IA',
                    [
                        'tentativa' => $tentativa,
                        'erro' => $e->getMessage()
                    ]
                );

                if (str_contains($e->getMessage(), 'limite de tokens')) {
                    break;
                }
            }
        }

        Logger::error(
            'ai/receitas',
            'Todas as tentativas de geração falharam',
            [
                'erro' => $ultimaException?->getMessage()
            ]
        );

        throw $ultimaException;
    }


    private function buildPrompt(string $texto): string
    {
        // Carrega categorias e subcategorias dinamicamente do banco
        $dados    = $this->carregarCategoriasDoBanco();
        $jsonCats = json_encode($dados['categorias'],    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $jsonSubs = json_encode($dados['subcategorias'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return 'Você é um assistente especializado em transformar receitas em JSON estruturado.

IMPORTANTE:
- Retorne APENAS JSON válido
- Não escreva explicações
- Não use markdown
- Não use ```json
- Nunca invente categorias/subcategorias fora da lista
- O JSON deve seguir EXATAMENTE a estrutura abaixo
- O resumo deve ser entretivo, lúdico, criativo para chamar atenção, até 255 caracteres
- Sempre gere arrays válidos
- Sempre gere etapas organizadas
- Sempre gere ingredientes agrupados
- Dificuldade deve ser um número inteiro entre 1 e 3
- "tempo_min" NUNCA DEVE SER MAIOR QUE 59
- Caso não exista grupo de ingredientes, use:
  "titulo": ""

TIPOS DE PORÇÃO VÁLIDOS:
- "Porções"
- "Pedaços"
- "Pratos"
- "Fatias"
- "Unidades"

CATEGORIAS DISPONÍVEIS:
' . $jsonCats . '

SUBCATEGORIAS DISPONÍVEIS:
' . $jsonSubs . '

ESTRUTURA OBRIGATÓRIA:
{
  "titulo": "",
  "resumo": "",
  "dificuldade": 0,
  "tempo_min": 0,
  "tempo_hora": 0,
  "porcoes": 0,
  "tipo_porcao": "",
  "categoria": "",
  "idCategoria": "",
  "subcategoria": "",
  "idSubcategoria": "",
  "ingredientes": [
    {
      "titulo": "",
      "itens": [
        ""
      ]
    }
  ],
  "etapas": [
    {
      "titulo": "",
      "ordem": 1,
      "descricoes": [
        {
          "descricao": "",
          "ordem": 1
        }
      ]
    }
  ]
}

EXEMPLOS:
- categoria: "Comemorativas, Sobremesas"
- idCategoria: "3, 1"
- subcategoria: "Natal"
- idSubcategoria: "22"

RECEITA:
' . $texto;
    }

    private function carregarCategoriasDoBanco(): array
    {
        try {
            $adminModel = new \App\Model\AdminCategoriaModel();
            return $adminModel->listarParaPromptIA();
        } catch (\Exception $e) {
            return ['categorias' => [], 'subcategorias' => []];
        }
    }

    private function callOpenRouter(string $prompt): string
    {
        $data = [
            "model" => "openai/gpt-oss-20b:free",
            "messages" => [
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ],
            "temperature" => 0.2,
            "max_tokens" => 2500
        ];

        $ch = curl_init($this->apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json",
            "HTTP-Referer: https://brafood.page.gd",
            "X-Title: BraFood"
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {

            $erro = curl_error($ch);

            Logger::error(
                'ai/receitas',
                'Erro CURL na OpenRouter',
                [
                    'erro' => $erro
                ]
            );

            curl_close($ch);

            throw new Exception($erro);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode >= 400) {

            Logger::error(
                'ai/receitas',
                'OpenRouter retornou erro HTTP',
                [
                    'http_code' => $httpCode,
                    'response' => $result
                ]
            );

            throw new Exception(
                'Erro na OpenRouter. HTTP ' . $httpCode
            );
        }

        return $result;
    }

    private function extractJson(string $response): array
    {
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            Logger::error(
                'ai/receitas',
                'Falha ao decodificar resposta da OpenRouter',
                [
                    'erro_json' => json_last_error_msg(),
                    'response' => $response
                ]
            );

            throw new Exception('Resposta inválida da OpenRouter');
        }

        if (isset($data['error'])) {

            Logger::error(
                'ai/receitas',
                'OpenRouter retornou erro',
                [
                    'error' => $data['error']
                ]
            );

            throw new Exception(
                $data['error']['message']
                    ?? 'Erro desconhecido da OpenRouter'
            );
        }

        // NOVO
        $finishReason = $data['choices'][0]['finish_reason'] ?? null;

        Logger::info(
            'ai/receitas',
            'Resposta da IA finalizada',
            [
                'finish_reason' => $finishReason,
                'completion_tokens' => $data['usage']['completion_tokens'] ?? null
            ]
        );

        if ($finishReason === 'length') {
            throw new Exception('Resposta da IA foi cortada por limite de tokens.');
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        if (!$content) {

            Logger::error(
                'ai/receitas',
                'Resposta da IA sem conteúdo',
                [
                    'response' => $response
                ]
            );

            throw new Exception(
                'Resposta da IA sem conteúdo'
            );
        }

        $content = trim($content);

        $content = str_replace(
            ['```json', '```'],
            '',
            $content
        );

        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            Logger::error(
                'ai/receitas',
                'IA retornou JSON inválido',
                [
                    'erro_json' => json_last_error_msg(),
                    'content' => $content
                ]
            );

            throw new Exception(
                'JSON inválido retornado pela IA: '
                    . json_last_error_msg()
            );
        }

        return $json;
    }
}
