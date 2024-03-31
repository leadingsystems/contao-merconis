WITH
    cte1 AS (
        /*
         all pids and all attributes, filling combinations that
         don't actually exist in tl_ls_shop_attribute_allocation with "0"
         */
        SELECT pa.pid,
               attr.attributeID,
               COALESCE(p.attributeValueID, 0) AS attributeValueID

        FROM (
                 /* list of all distinct pids for variationGroupCode */
                 SELECT DISTINCT allo.pid
                 FROM tl_ls_shop_attribute_allocation allo
                          JOIN tl_ls_shop_product prod
                               ON allo.pid = prod.id
                 WHERE prod.variationGroupCode = :variationGroupCode
         ) AS pa

        CROSS JOIN (
            /* list of all distinct attributeIDs pids for variationGroupCode */
            SELECT DISTINCT allo.attributeID
            FROM tl_ls_shop_attribute_allocation allo
                     JOIN tl_ls_shop_product prod
                          ON allo.pid = prod.id
            WHERE prod.variationGroupCode = :variationGroupCode
        ) AS attr

        /*
        Join all actually existing rows so that we get null values for the attributeValueID
        for those rows that only exist in the cartesian product of the pid and the attributeID
        */
         LEFT JOIN tl_ls_shop_attribute_allocation AS p
            ON pa.pid = p.pid
                AND attr.attributeID = p.attributeID
    )

SELECT pid, attributeID, attributeValueID
FROM cte1
WHERE attributeValueID = 0

