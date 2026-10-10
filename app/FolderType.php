<?php

namespace App;

enum FolderType: string
{
    case Group = 'group';
    case DocumentType = 'document_type';
    case Temporary = 'temporary';
}
