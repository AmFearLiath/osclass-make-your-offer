CREATE TABLE /*TABLE_PREFIX*/t_item_offers (
   `pk_i_id` int(10) NOT NULL AUTO_INCREMENT,
  `i_item_id` int(10) NOT NULL,
  `i_user_id` int(10) DEFAULT NULL,
  `s_offer_value` bigint(30) DEFAULT NULL,
  `s_offer_minimum` bigint(30) DEFAULT NULL,
  `i_offer_status` int(1) DEFAULT NULL,
  `i_notifications_status` int(1) DEFAULT NULL,
  PRIMARY KEY (`pk_i_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';