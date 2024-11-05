<?php

namespace AutomaticDiscount\Model;

use AutomaticDiscount\Model\Base\AutomaticDiscountQuery as BaseAutomaticDiscountQuery;
use AutomaticDiscount\Model\Map\AutomaticDiscountTableMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Propel\Runtime\Exception\PropelException;
use Thelia\Model\CouponQuery;
use Thelia\Model\Map\CouponTableMap;

/**
 * Class AutomaticDiscountQuery
 * @package AutomaticDiscount\Model
 * @author Baixas Alban <abaixas@openstudio.fr>
 */
class AutomaticDiscountQuery extends BaseAutomaticDiscountQuery
{

    /**
     * find all enabled automatic discount
     * @throws PropelException
     */
    public function findAllEnabledAutomaticDiscount(): array
    {
        $query = CouponQuery::create()->filterByIsEnabled(true);

        $query->select('code');

        $join = new Join();
        $join->setJoinType(Criteria::INNER_JOIN);
        $join->addExplicitCondition(
            CouponTableMap::TABLE_NAME,
            'id',
            null,
            AutomaticDiscountTableMap::TABLE_NAME,
            'coupon_id',
            'automatic_discount_join'
        );

        $query->addJoinObject($join, 'automatic_discount_join');

        return $query->find()->toArray();
    }
}
