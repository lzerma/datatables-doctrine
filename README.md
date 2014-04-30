Datatables - Doctrine 
============
This module provides a interface for integration with twitter bootstrap.

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

