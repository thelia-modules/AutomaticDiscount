
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- automatic_discount
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `automatic_discount`;

CREATE TABLE `automatic_discount`
(
    `coupon_id` INTEGER NOT NULL,
    PRIMARY KEY (`coupon_id`),
    INDEX `fk_coupon_customer_credit_coupon_id_idx` (`coupon_id`),
    CONSTRAINT `fk_coupon_automatic_discount_coupon_id`
        FOREIGN KEY (`coupon_id`)
        REFERENCES `coupon` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
