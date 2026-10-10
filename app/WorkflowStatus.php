<?php

namespace App;

enum WorkflowStatus: string
{
    case Draft = 'draft';
    case PendingVerification = 'pending_verification';
    case RevisionRequired = 'revision_required';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::PendingVerification => 'Menunggu verifikasi',
            self::RevisionRequired => 'Perlu diperbaiki',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-700',
            self::PendingVerification => 'bg-amber-50 text-amber-800',
            self::RevisionRequired => 'bg-orange-50 text-orange-800',
            self::Approved => 'bg-green-50 text-success',
            self::Rejected => 'bg-primary-soft text-primary',
        };
    }
}
