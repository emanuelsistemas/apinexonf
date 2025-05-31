<?php
require_once '../vendor/autoload.php';

// Headers de resposta (CORS gerenciado pelo Nginx)
header('Content-Type: application/json');

// Responder OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carregar configurações
if (file_exists('../.env')) {
    $dotenv = parse_ini_file('../.env');
    foreach ($dotenv as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Router simples
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Middleware de autenticação (opcional)
function verificarToken() {
    if (isset($_ENV['API_TOKEN']) && !empty($_ENV['API_TOKEN'])) {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';
        
        if ($token !== 'Bearer ' . $_ENV['API_TOKEN']) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido']);
            exit();
        }
    }
}

// Rotas da API
switch ($uri) {
    // === ROTAS NFe (Modelo 55) ===
    case '/api/gerar-nfe':
        if ($method === 'POST') {
            verificarToken();
            require_once '../src/Controllers/GerarNFeController.php';
            $controller = new \NexoNFe\Controllers\GerarNFeController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    case '/api/enviar-sefaz':
        if ($method === 'POST') {
            verificarToken();
            require_once '../src/Controllers/EnviarSefazController.php';
            $controller = new \NexoNFe\Controllers\EnviarSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    case '/api/consultar-nfe':
        if ($method === 'GET') {
            verificarToken();
            require_once '../src/Controllers/ConsultarNFeController.php';
            $controller = new \NexoNFe\Controllers\ConsultarNFeController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    // === ROTAS NFC-e (Modelo 65) ===
    case '/api/gerar-nfce':
        if ($method === 'POST') {
            verificarToken();
            require_once '../src/Controllers/GerarNFCeController.php';
            $controller = new \NexoNFe\Controllers\GerarNFCeController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    case '/api/enviar-nfce-sefaz':
        if ($method === 'POST') {
            verificarToken();
            require_once '../src/Controllers/EnviarNFCeSefazController.php';
            $controller = new \NexoNFe\Controllers\EnviarNFCeSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    case '/api/consultar-nfce':
        if ($method === 'GET') {
            verificarToken();
            require_once '../src/Controllers/ConsultarNFCeController.php';
            $controller = new \NexoNFe\Controllers\ConsultarNFCeController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    case '/api/cancelar-nfce':
        if ($method === 'POST') {
            verificarToken();
            require_once '../src/Controllers/CancelarNFCeController.php';
            $controller = new \NexoNFe\Controllers\CancelarNFCeController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    case '/api/gerar-qrcode-nfce':
        if ($method === 'POST') {
            verificarToken();
            require_once '../src/Controllers/GerarQRCodeNFCeController.php';
            $controller = new \NexoNFe\Controllers\GerarQRCodeNFCeController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    case '/api/config-nfce':
        if ($method === 'GET') {
            echo json_encode([
                'valor_maximo' => 5000.00,
                'tipos_pagamento' => [
                    '01' => 'Dinheiro',
                    '02' => 'Cheque',
                    '03' => 'Cartão de Crédito',
                    '04' => 'Cartão de Débito',
                    '05' => 'Crédito Loja',
                    '10' => 'Vale Alimentação',
                    '11' => 'Vale Refeição',
                    '12' => 'Vale Presente',
                    '13' => 'Vale Combustível',
                    '15' => 'Boleto Bancário',
                    '99' => 'Outros'
                ],
                'ambiente_padrao' => 2,
                'serie_padrao' => 1,
                'consumidor_obrigatorio' => false
            ]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
        }
        break;
        
    // === ROTAS GERAIS ===
    // === ROTAS DE LOGS ===
    case "/api/logs":
        if ($method === "GET") {
            verificarToken();
            require_once "../src/Controllers/LogsController.php";
            $controller = new \NexoNFe\Controllers\LogsController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
        
    case "/api/logs/monitor":
        if ($method === "GET") {
            verificarToken();
            require_once "../src/Controllers/LogsController.php";
            $controller = new \NexoNFe\Controllers\LogsController();
            $controller->handleMonitor();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
        
    case "/api/logs/clear":
        if ($method === "POST") {
            verificarToken();
            require_once "../src/Controllers/LogsController.php";
            $controller = new \NexoNFe\Controllers\LogsController();
            $controller->handleClear();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
        
    // === ROTAS GERAIS ===

    case '/api/status':

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
        echo json_encode([

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
            'status' => 'API NFe/NFC-e Online',

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
            'timestamp' => date('Y-m-d H:i:s'),

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
            'version' => '1.1.0',

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
            'php_version' => PHP_VERSION,

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
            'domain' => 'apinfe.nexopdv.com',

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
            'modelos_suportados' => [

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
                'NFe' => 'Modelo 55 - Nota Fiscal Eletrônica',

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
                'NFC-e' => 'Modelo 65 - Nota Fiscal de Consumidor Eletrônica'

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
            ]

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
        ]);

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
        break;

    case "/api/status-sefaz":
        if ($method === "GET") {
            require_once "../src/Controllers/StatusSefazController.php";
            $controller = new \NexoNFe\Controllers\StatusSefazController();
            $controller->handle();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido"]);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint não encontrado']);
        break;
}
?>
