<?php

namespace Nasus\WebmanUtils\Model\Macro;

use Illuminate\Database\Query\Builder as DbBuilder;
use Webman\Bootstrap;
use Workerman\Worker;

class Builder implements Bootstrap
{
    public static function start(?Worker $worker)
    {
        // 模糊查询
        DbBuilder::macro('whenLike', function ($params = [], $prefix = null) {
            $params = array_filter($params);
            return $this->when(!empty($params), function (DbBuilder $builder) use ($prefix, $params) {
                foreach ($params as $key => $value) {
                    $field = is_null($prefix) ? $key : sprintf('%s.%s', $prefix, $key);
                    $builder = $builder->where($field, 'like', "%" . $value . "%");
                }
                return $builder;
            });
        });

        DbBuilder::macro('whenOrLike', function ($params = [], $prefix = null) {
            $params = array_filter($params);
            return $this->when(!empty($params), function (DbBuilder $builder) use ($prefix, $params) {
                foreach ($params as $key => $value) {
                    $field = is_null($prefix) ? $key : sprintf('%s.%s', $prefix, $key);
                    $builder = $builder->orWhere($field, 'like', "%" . $value . "%");
                }
                return $builder;
            });
        });

        DbBuilder::macro('whenWhere', function ($params = [], $prefix = null) {
            $params = array_filter($params);
            return $this->when(!empty($params), function (DbBuilder $builder) use ($prefix, $params) {
                foreach ($params as $key => $value) {
                    $field = is_null($prefix) ? $key : sprintf('%s.%s', $prefix, $key);
                    $builder = $builder->where($field, $value);
                }
                return $builder;
            });
        });

        // 时间范围
        DbBuilder::macro('whenDate', function ($params = [], $prefix = null) {
            $params = array_filter($params);
            return $this->when(!empty($params), function (DbBuilder $builder) use ($prefix, $params) {
                foreach ($params as $key => $value) {
                    $field = is_null($prefix) ? $key : sprintf('%s.%s', $prefix, $key);
                    $builder = $builder
                        ->where($field, '>=', date('Y-m-d 00:00:00', strtotime($value[0])))
                        ->where($field, '<=', date('Y-m-d 23:59:59', strtotime($value[1])));
                }
                return $builder;
            });
        });

        // 排序
        DbBuilder::macro('whenOrderBy', function ($fieldsMap = []) {
            $sortField = request()->input('sortField');
            $sortOrder = request()->input('sortOrder');

            $field = empty($fieldsMap[$sortField]) ? $sortField : $fieldsMap[$sortField];
            return $this->when(!empty($field) && !empty($sortOrder),
                function (DbBuilder $builder) use ($field, $sortOrder) {
                    return $builder->orderBy($field, $sortOrder == 'ascend' ? 'asc' : 'desc');
                });
        });

        DbBuilder::macro('whenWhereIn', function ($params = [], $prefix = null) {
            $params = array_filter($params);
            return $this->when(!empty($params), function (DbBuilder $builder) use ($prefix, $params) {
                foreach ($params as $key => $value) {
                    $field = is_null($prefix) ? $key : sprintf('%s.%s', $prefix, $key);
                    $builder = $builder->whereIn($field, $value);
                }
                return $builder;
            });
        });
    }
}