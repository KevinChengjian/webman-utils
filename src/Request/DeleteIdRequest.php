<?php

namespace Nasus\WebmanUtils\Request;

use Nasus\WebmanUtils\Annotation\ParameterDoc;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @property int $id 数据ID
 */
class DeleteIdRequest extends AbstractRequest
{
    #[NotBlank(message: '请选择要删除的数据')]
    #[ParameterDoc(field: 'id', name: '数据ID', type: 'int')]
    protected int $id;
}