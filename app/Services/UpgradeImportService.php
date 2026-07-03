<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class UpgradeImportService
{
    private const NAME_HEADERS = [
        'nomecliente',
        'cliente',
        'nome',
        'razaosocial',
    ];

    private const FIRST_PHONE_HEADERS = [
        'primeirocontato',
        '1contato',
        'telefone1',
        'telefoneprincipal',
        'telefone',
        'celular',
    ];

    private const SECOND_PHONE_HEADERS = [
        'segundocontato',
        '2contato',
        'telefone2',
        'telefonedois',
        'telefoneadicional',
        'contato2',
    ];

    public function importar(UploadedFile $arquivo): array
    {
        $spreadsheet = IOFactory::load($arquivo->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if ($rows === [] || count($rows) < 2) {
            throw new RuntimeException('A planilha nao contem dados validos para importacao.');
        }

        $headerRow = array_shift($rows);
        $columnMap = $this->mapearColunas($headerRow);

        if (! isset($columnMap['nome_cliente'])) {
            throw new RuntimeException('Nao foi possivel localizar a coluna com o nome do cliente.');
        }

        $contacts = [];

        foreach ($rows as $lineNumber => $row) {
            $nome = $this->cleanValue($row[$columnMap['nome_cliente']] ?? null);

            if ($nome === '') {
                continue;
            }

            $primeiro = isset($columnMap['primeiro_contato'])
                ? $this->cleanValue($row[$columnMap['primeiro_contato']] ?? null)
                : null;

            $segundo = isset($columnMap['segundo_contato'])
                ? $this->cleanValue($row[$columnMap['segundo_contato']] ?? null)
                : null;

            if ($primeiro === '' && $segundo === '') {
                $primeiro = null;
                $segundo = null;
            }

            $contacts[] = [
                'linha_planilha' => (int) $lineNumber,
                'nome_cliente' => $nome,
                'primeiro_contato' => $primeiro ?: null,
                'segundo_contato' => $segundo ?: null,
                'contato_preferido' => $this->preferenciaPadrao($primeiro, $segundo),
                'status_envio' => 'aguardando',
            ];
        }

        if ($contacts === []) {
            throw new RuntimeException('Nenhum cliente valido foi encontrado na planilha.');
        }

        return [
            'contacts' => $contacts,
            'total' => count($contacts),
            'sheet_name' => $sheet->getTitle(),
        ];
    }

    private function mapearColunas(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $column => $header) {
            $normalized = $this->normalizeHeader($header);

            if ($normalized === '') {
                continue;
            }

            if ($this->containsAny($normalized, self::SECOND_PHONE_HEADERS)) {
                $map['segundo_contato'] ??= $column;
                continue;
            }

            if ($this->containsAny($normalized, self::FIRST_PHONE_HEADERS)) {
                $map['primeiro_contato'] ??= $column;
                continue;
            }

            if ($this->containsAny($normalized, self::NAME_HEADERS)) {
                $map['nome_cliente'] ??= $column;
                continue;
            }
        }

        return $map;
    }

    private function containsAny(string $value, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHeader(mixed $value): string
    {
        $text = Str::ascii(trim((string) $value));
        $text = strtolower($text);

        return preg_replace('/[^a-z0-9]+/', '', $text) ?: '';
    }

    private function cleanValue(mixed $value): string
    {
        $text = trim((string) $value);

        return preg_replace('/\s+/', ' ', $text) ?: '';
    }

    private function preferenciaPadrao(?string $primeiro, ?string $segundo): string
    {
        if ($primeiro !== null && $primeiro !== '') {
            return 'primeiro';
        }

        if ($segundo !== null && $segundo !== '') {
            return 'segundo';
        }

        return 'auto';
    }
}
