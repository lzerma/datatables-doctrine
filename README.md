Datatables - Doctrine 
============
This module provides a interface for integration with twitter bootstrap.

First you should create dd in your doctrine configuration this line
```
return array(
    'doctrine' => array(
        ...,
        'configuration' => array(
            'orm_default' => array(
                'string_functions' => array(
                    "remove_accents" => "Datatables\Doctrine\Dql\RemoveAccents"
                )
            ),
        ),
    )
);
```

Create the function in postgres

```
-- Function: remove_accents(character varying)

-- DROP FUNCTION remove_accents(character varying);

CREATE OR REPLACE FUNCTION remove_accents(character varying)
  RETURNS character varying AS
$BODY$
SELECT TRANSLATE($1, 'áéíóúàèìòùãõâêîôôäëïöüçÁÉÍÓÚÀÈÌÒÙÃÕÂÊÎÔÛÄËÏÖÜÇ', 'aeiouaeiouaoaeiooaeioucAEIOUAEIOUAOAEIOOAEIOUC')
$BODY$
  LANGUAGE sql VOLATILE
  COST 100;
ALTER FUNCTION remove_accents(character varying)
  OWNER TO mpc2;
``` 



For work with your project, you must need extends the Datatables\Doctrine\DefaultRepository

Example: YourRepository.php
```
class YourRepository extends Datatables\Doctrine\DefaultRepository {
...
```
```
$this->setAColumns(
                array(
                    array("campo"=>"district.id","type"=>"number"),
                    array("campo"=>"district.name","type"=>"string"),
                    array("campo"=>"city.name","type"=>"string")
                )
        );
        $this->setAColumnsArray(
                array(
                    array("campo" => "id", "html" =>
                        '
                            <div class="checkbox check-default">
                                <input id="checkbox{campo}" name="bairro[]" type="checkbox" value="{campo}"> 
                                <label for="checkbox{campo}"></label>
                            </div>
                            '
                    ),
                    array("campo" => "name"),
                    array("campo" => "city")
                )
        );

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select("district");
        $qb->from("Base\Entity\District", "district");
        $qb->innerJoin("Base\Entity\City", "city", "WITH", "city = district.city");

        return $this->getDatatables($qb, $params);
```

