WITH
    allocations_with_null_values AS (
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

# SELECT pid, attributeID, attributeValueID
# FROM cte1
# WHERE attributeValueID = 0

SELECT
    av.pid,
    COUNT(DISTINCT av.attributeID) AS matchingAttributesCount

FROM (
         SELECT
             pa.pid,
             pa.attributeID,
             COALESCE(pa.attributeValueID, 0) AS attributeValueID,
             p2.matchForMandatoryAttributeValueCombination

         FROM allocations_with_null_values pa

                  /*
                   * Join only those records with pids that definitely are a match for the
                   * mandatory attribute/value combination so that we get a flag for all rows
                   * with pids that we want to consider for the end result.
                   */
                  LEFT JOIN (
             SELECT
                 pid,
                 attributeID,
                 '1' AS matchForMandatoryAttributeValueCombination
             FROM allocations_with_null_values
             WHERE pid IN (
                 SELECT DISTINCT pid
                 FROM allocations_with_null_values
                 WHERE (attributeID = 32 AND attributeValueID = 0)
             )
         ) AS p2
                            ON pa.pid = p2.pid
                                AND pa.attributeID = p2.attributeID

         WHERE p2.matchForMandatoryAttributeValueCombination IS NOT NULL
     ) av
WHERE
    ((av.attributeID = '31' AND av.attributeValueID = '7079') OR (av.attributeID = '32' AND av.attributeValueID = '0'))
GROUP BY av.pid
ORDER BY matchingAttributesCount DESC

