<?php

namespace App\Service;

use Exception;

require_once __DIR__ . '/../config/config.php';

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

        $response = $this->callOpenRouter($prompt);

        return $this->extractJson($response);
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
- "tempo_min" vai até 59
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
            "max_tokens" => 1200
        ];

        $ch = curl_init($this->apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json",
            "HTTP-Referer: http://localhost",
            "X-Title: BraFood"
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return $result;
    }

    private function extractJson(string $response): array
    {
        $data = json_decode($response, true);

        if (isset($data["error"])) {
            throw new Exception(
                $data["error"]["message"] ?? "Erro desconhecido"
            );
        }

        $content =
            $data["choices"][0]["message"]["content"] ?? null;

        if (!$content) {
            throw new Exception(
                "Resposta inválida da IA: " . $response
            );
        }

        $content = trim($content);

        $content = str_replace(
            ["```json", "```"],
            "",
            $content
        );

        preg_match('/\{.*\}/s', $content, $matches);

        if (!isset($matches[0])) {
            throw new Exception(
                "Nenhum JSON encontrado: " . $content
            );
        }

        $json = json_decode($matches[0], true);

        if (!$json) {
            throw new Exception(
                "Falha ao converter JSON: " . $matches[0]
            );
        }

        return $json;
    }
}
