<?php
// Insira a sua chave real aqui
// Configure GEMINI_API_KEY no ambiente do servidor.
$apiKey = getenv('GEMINI_API_KEY');
$apiKey = $apiKey !== false ? trim($apiKey) : '';
$url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

echo "<h3>Modelos disponíveis para esta chave:</h3>";
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";
?>