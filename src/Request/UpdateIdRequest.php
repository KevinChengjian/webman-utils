<?php

namespace Nasus\WebmanUtils\Request;


use Nasus\WebmanUtils\Annotation\ParameterDoc;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @property int $id 数据ID
 */
class UpdateIdRequest extends AbstractRequest
{
    #[NotBlank(message: '请选择要修改的数据')]
    #[ParameterDoc(field: 'id', name: '数据ID', type: 'int')]
    protected int $id;
}