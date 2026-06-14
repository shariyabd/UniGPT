<?php

namespace App\Policies;

use App\Domain\User\Models\User;
use App\Enums\DocumentStatus;
use App\Enums\Permission;
use App\Models\Document;

class DocumentPolicy
{
    /**
     * Anyone who may see the document in the library can view/download it:
     * approved + visibility allows their role (admins see everything).
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($document->status !== DocumentStatus::APPROVED) {
            return false;
        }

        return Document::approved()->visibleTo($user)->whereKey($document->id)->exists();
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission(Permission::APPROVE_DOCUMENT);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermission(Permission::DELETE_DOCUMENT)
            || $document->uploaded_by === $user->id;
    }
}
