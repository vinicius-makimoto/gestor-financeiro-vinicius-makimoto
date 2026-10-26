<?php
declare(strict_types=1);

final class JsonRepository
{
    public function __construct(private string $name) {}
    private function path(): string { return DATA_DIR . DIRECTORY_SEPARATOR . $this->name . '.json'; }

    public function all(): array
    {
        $path = $this->path();
        if (!is_file($path)) return [];
        $handle = fopen($path, 'rb');
        if (!$handle) throw new RuntimeException('Não foi possível abrir os dados.');
        try {
            if (!flock($handle, LOCK_SH)) throw new RuntimeException('Não foi possível ler os dados.');
            $contents = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally { fclose($handle); }
        $data = json_decode($contents ?: '[]', true);
        if (!is_array($data)) throw new RuntimeException('Arquivo de dados inválido: ' . $this->name);
        return $data;
    }

    public function saveAll(array $items): void
    {
        $path = $this->path();
        if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0770, true) && !is_dir(DATA_DIR)) throw new RuntimeException('Não foi possível criar a pasta de dados.');
        if (!is_dir(BACKUP_DIR)) mkdir(BACKUP_DIR, 0770, true);
        $lock = fopen($path . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Não foi possível bloquear os dados para gravação.');
        try {
            if (is_file($path)) {
                $backup = BACKUP_DIR . DIRECTORY_SEPARATOR . $this->name . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json';
                if (!copy($path, $backup)) throw new RuntimeException('Não foi possível criar o backup local.');
            }
            $json = json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $temp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
            if (file_put_contents($temp, $json . PHP_EOL, LOCK_EX) === false) {
                @unlink($temp);
                throw new RuntimeException('Não foi possível gravar o arquivo temporário.');
            }
            if (!@rename($temp, $path)) {
                // No Windows, rename may not replace an existing target file.
                if (is_file($path) && @unlink($path) && @rename($temp, $path)) {
                    // The previous version was already copied to the backup folder.
                } else {
                    @unlink($temp);
                    throw new RuntimeException('Não foi possível substituir o arquivo de dados. Verifique as permissões da pasta storage.');
                }
            }
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
}

