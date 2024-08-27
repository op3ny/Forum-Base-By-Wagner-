<?php
// Verificar se o parâmetro 'file' está presente na URL
$file = $_GET['file'] ?? null;

if ($file) {
    $filePath = __DIR__ . '/../' . $file; // Caminho completo para o arquivo

    // Verificar se o arquivo existe
    if (file_exists($filePath)) {
        // Definir os cabeçalhos para forçar o download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        // Limpar o buffer de saída
        flush();

        // Ler o arquivo e enviar para o navegador
        readfile($filePath);
        exit;
    } else {
        echo "Arquivo não encontrado!";
    }
} else {
    echo "Nenhum arquivo especificado para download.";
}
?>
