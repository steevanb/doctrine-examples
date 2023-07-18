<?php

declare(strict_types=1);

namespace DoctrineExamples\CollectionUpdate\EventListener;

class EventsResult
{
    public bool $preRemoveComment;

    public bool $postRemoveComment;

    public bool $prePersistComment;

    public bool $prePersistUser;

    public bool $postPersistComment;

    public bool $postPersistUser;

    public bool $preUpdateComment;

    public bool $preUpdateUser;

    public bool $postUpdateComment;

    public bool $postUpdateUser;

    public bool $postLoadComment;

    public bool $postLoadUser;

    public bool $preFlush;

    public bool $onFlush;

    public bool $postFlush;

    public bool $onClear;
}
