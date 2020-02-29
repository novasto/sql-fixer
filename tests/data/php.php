<?php

function test()
{
    $q = '';

    $aa = <<<SQL
with
     
     -- comment
     as1 as (
         {$q}
     ),
          as2 as (
         {$q}
     )
select * from table1 where id = 3
SQL;

    $bb = <<< 'SQL'
select * from table1 
    as alias_table1 join table2 
on col1=col2 order by col3
SQL;

    $cc = <<<"SQL"
update table1 set col1 = ?, col2 = ? where id = ?
SQL;
}