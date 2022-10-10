<?php

namespace Toshkq93\Components\Enums;

final class MethodsByClassEnum
{
    const INDEX = 'index';
    const SHOW = 'show';
    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';
    const STORE = 'store';
    const ALL = 'all';

    const CREATE_METHOD = 'Create';
    const UPDATE_METHOD = 'Update';

    const CONTROLLER_METHODS = [
        self::INDEX,
        self::STORE,
        self::SHOW,
        self::UPDATE,
        self::DELETE
    ];

    const SERVICE_METHODS = [
        self::ALL,
        self::CREATE,
        self::SHOW,
        self::UPDATE,
        self::DELETE
    ];

    const REPOSITORY_METHODS = [
        self::ALL,
        self::CREATE,
        self::SHOW,
        self::UPDATE,
        self::DELETE
    ];

    const REQUEST_NAMES = [
        self::CREATE_METHOD,
        self::UPDATE_METHOD,
    ];

    const DTO_INPUT_NAMES = [
        self::CREATE_METHOD,
        self::UPDATE_METHOD,
    ];
}
