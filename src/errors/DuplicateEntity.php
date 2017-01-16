<?php

namespace DevGroup\FlexIntegration\errors;

class DuplicateEntity extends BaseException
{
    public $message = 'Duplicate entity found in document.';
}
