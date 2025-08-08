<?php

namespace Nasus\WebmanUtils\Request;

use Nasus\WebmanUtils\Annotation\ParameterDoc;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @property int $page 页码
 * @property int $pageSize 分页条数
 * @property string $sortField 排序字段
 * @property string $sortOrder 排序方式:ascend(升序),descend(降序)
 */
class PagingRequest extends AbstractRequest
{
    #[NotBlank(message: '分页参数错误')]
    #[Type(type: 'integer', message: '分页参数错误')]
    #[ParameterDoc(field: 'page', name: '页码', type: 'int')]
    protected int $page;

    #[NotBlank(message: '分页参数错误')]
    #[ParameterDoc(field: 'page', name: 'limit', type: 'int')]
    protected int $pageSize;

    #[ParameterDoc(field: 'sort_field', name: '排序字段', type: 'int')]
    protected string $sortField;

    #[ParameterDoc(field: 'sort_order', name: '排序方式', type: 'int')]
    protected string $sortOrder;
}