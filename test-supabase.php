<?php
// Teste simples da conexão Supabase

// Carregar configurações
if (file_exists('.env')) {
    $dotenv = parse_ini_file('.env');
    foreach ($dotenv as $key => $value) {
        $_ENV[$key] = $value;
    }
}

echo "🧪 Teste de Conexão Supabase\n";
echo "============================\n\n";

// Verificar configurações
echo "📋 Configurações:\n";
echo "URL: " . ($_ENV['SUPABASE_URL'] ?? 'NÃO CONFIGURADA') . "\n";
echo "Key: " . (isset($_ENV['SUPABASE_KEY']) ? 'CONFIGURADA (' . strlen($_ENV['SUPABASE_KEY']) . ' chars)' : 'NÃO CONFIGURADA') . "\n\n";

if (empty($_ENV['SUPABASE_URL']) || empty($_ENV['SUPABASE_KEY'])) {
    echo "❌ Configurações do Supabase não encontradas!\n";
    exit(1);
}

// Teste de conexão
echo "🔗 Testando conexão...\n";

$url = $_ENV['SUPABASE_URL'] . '/rest/v1/empresas?select=id,name&limit=3';

$headers = [
    'Authorization: Bearer ' . $_ENV['SUPABASE_KEY'],
    'apikey: ' . $_ENV['SUPABASE_KEY'],
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erro cURL: $error\n";
    exit(1);
}

echo "HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Conexão bem-sucedida!\n";
    echo "📊 Empresas encontradas: " . count($data) . "\n";
    
    if (!empty($data)) {
        echo "\n📋 Primeiras empresas:\n";
        foreach (array_slice($data, 0, 3) as $empresa) {
            echo "- ID: " . ($empresa['id'] ?? 'N/A') . " | Nome: " . ($empresa['name'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "❌ Erro HTTP: $httpCode\n";
    echo "Resposta: $response\n";
    exit(1);
}

echo "\n🎉 Teste concluído com sucesso!\n";
