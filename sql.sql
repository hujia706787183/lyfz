/**  CRM中 用到的一些SQL */

/**  清理订单产品列表 */

SELECT
	order_product.product_name,
	order_product.id AS order_product_id,
	order_product.create_time AS create_time
FROM
	lyfz_r_order_product order_product
INNER JOIN lyfz_receivables receivables ON receivables.receivables_id = order_product.order_id
AND receivables.is_deleted <> 0
ORDER BY
	order_product.create_time DESC
LIMIT 0,
 15

 /** 导出某产品的销售记录 */

SELECT
	product_name AS '产品名',
	c. NAME AS '客户名称',
	FROM_UNIXTIME(op.create_time) AS '购买时间',
	co. NAME AS '联系人名称',
	co.telephone AS '联系人电话',
	r.price AS '应付',
	ifnull(sum(ro.money), 0) AS '已付',
	ifnull(r.price - sum(ro.money), 0) AS '欠款',
	u. NAME AS '业绩负责人',
	r.description AS '订单备注'
FROM
	lyfz_r_order_product op
JOIN lyfz_customer c ON c.customer_id = op.customer_id
JOIN lyfz_contacts co ON c.contacts_id = co.contacts_id
JOIN lyfz_receivables r ON op.order_id = r.receivables_id
LEFT JOIN lyfz_receivingorder ro ON ro.receivables_id = r.receivables_id and ro.is_deleted = 0
JOIN lyfz_user u ON u.role_id = r.owner_role_id
WHERE
	product_name LIKE "%小程序%"
OR product_name LIKE "%网鱼%"
and op.is_deleted = 0
GROUP BY
	op.id
ORDER BY
	op.create_time DESC


/** */

SELECT
	*, (总消费-总收款) as 欠款
FROM
	(
		SELECT
			`customer`.`customer_id` AS `customer_id`,
			`customer`.`name` AS `客户名称`,
			FROM_UNIXTIME(`customer`.`create_time`) AS `创建时间`,
			lyfz_user.name as `负责人`,
			`contacts`.`name` AS `联系人名`,
			`customer`.`address` AS `联系地址`,
			`contacts`.`telephone` AS `联系电话`,
			FROM_UNIXTIME(`receivables`.`create_time`) AS `首次下单时间`,
			receivables.name as `订单标题`
		FROM
			(
				(
					`lyfz_receivables` `receivables`
					LEFT JOIN `lyfz_customer` `customer` ON (
						(
							(
								`receivables`.`customer_id` = `customer`.`customer_id`
							)
							AND (
								`receivables`.`is_deleted` = 0
							)
						)
					)
				)
				LEFT JOIN `lyfz_contacts` `contacts` ON (
					(
						`contacts`.`contacts_id` = `customer`.`contacts_id`
					)
				)
				JOIN lyfz_user on customer.owner_role_id = lyfz_user.role_id
			)
		WHERE
			(`customer`.`is_deleted` = 0)
		ORDER BY
			`receivables`.`create_time`
	) t
join (
	SELECT
		c.customer_id, sum(r.price) as `总消费`
	FROM
		lyfz_customer c
	JOIN lyfz_receivables r ON r.customer_id = c.customer_id
	where c.create_time > 1514736000 and r.is_deleted = 0
	group by c.customer_id
) o on o.customer_id = t.customer_id
left join (
SELECT
		c.customer_id, sum(ro.money) as `总收款`
	FROM
		lyfz_customer c
	join lyfz_receivables r ON r.customer_id = c.customer_id
	JOIN lyfz_receivingorder ro ON r.receivables_id = ro.receivables_id
	where c.create_time > 1514736000 and r.is_deleted = 0 and ro.is_deleted = 0
	group by c.customer_id
) sk on sk.customer_id = t.customer_id
where 首次下单时间 > '2018-01-01'
GROUP BY
	t.`customer_id`



/** 客户订单,收款情况查询 */
SELECT
	t2.*, sum(ro.money) as '客户总收款', 客户总应收款 - sum(ro.money) as '客户总欠款'
FROM
	(
		SELECT
			t1.*, sum(r.price) AS '客户总应收款'
		FROM
			(
				SELECT
					r.receivables_id,
					c.customer_id,
					c. NAME AS '客户名称',
					r. NAME AS '订单标题',
					FROM_UNIXTIME(r.pay_time) AS '下单时间',
					r.price AS '应收款',
					sum(ro.money) AS '已收款',
					r.price - sum(ro.money) AS '欠款'
				FROM
					lyfz_receivables r
				JOIN lyfz_customer c ON c.customer_id = r.customer_id
				JOIN lyfz_receivingorder ro ON ro.receivables_id = r.receivables_id
				AND ro.is_deleted = 0
				WHERE
-- 					r. NAME LIKE '%服务费%'
-- 				AND 
r.is_deleted = 0
				GROUP BY
					r.receivables_id
				ORDER BY
					c.customer_id
			) t1
		JOIN lyfz_receivables r ON r.customer_id = t1.customer_id and r.is_deleted=0
		AND r.is_deleted = 0
		WHERE
			欠款 < 0
		GROUP BY
			t1.customer_id
	) t2
JOIN lyfz_receivables r ON r.customer_id = t2.customer_id and r.is_deleted=0
JOIN lyfz_receivingorder ro ON ro.receivables_id = r.receivables_id and ro.is_deleted=0
GROUP BY
	t2.customer_id
having (客户总应收款 - sum(ro.money)) = 0