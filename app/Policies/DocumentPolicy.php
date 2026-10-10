<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function viewAny(User $user): bool
    {
        return $this->create($user);
    }

    public function view(User $user, Document $document): bool
    {
        if (! $user->is_active || $document->trashed()) {
            return false;
        }

        return $user->is_active;
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function move(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }
}
