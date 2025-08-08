<?php

namespace Nasus\WebmanUtils\Request;

use Nasus\WebmanUtils\Annotation\ParameterDoc;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @property array $ids 数据ID
 */
class DeleteByIdsRequest extends AbstractRequest
{
    #[NotBlank(message: '请选择要删除的数据')]
    #[ParameterDoc(field: 'ids', name: '数据ID', type: 'array')]
    protected array $ids;
}