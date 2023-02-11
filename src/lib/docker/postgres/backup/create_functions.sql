CREATE OR REPLACE FUNCTION levenshtein_distance(str1 text, str2 text)
 RETURNS double precision
 LANGUAGE sql
AS $function$
    SELECT
        1-levenshtein(str1, str2)/
        (1e-20 + length(str1) + length(str2)
            - levenshtein(str1, str2))::float4
    ;
$function$
;