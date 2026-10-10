<?php

namespace App;

enum FileRole: string
{
    case Editable = 'editable';
    case FinalReference = 'final_reference';
    case Signed = 'signed';

    public function label(): string
    {
        return match ($this) {
            self::Editable => 'File kerja / dapat diedit', self::FinalReference => 'Dokumen final / referensi', self::Signed => 'Dokumen bertanda tangan'
        };
    }
}
