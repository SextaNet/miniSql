# simpleSql
###### por [Matias Trujillo](http://www.upp.cl)
Personalmente no me gusta sql (pero se que es bueno) , pero dado que poseo algunos servicios funcionando con el y la necesidad de sostener consultar **SQL simples, no complejas**, cree una serie de métodos para manipular **tablas sql**, de forma mas legible y escalable a condiciones y con valores concadenables de forma mas explicita en un array.

> Recomendación personal, conozca **SLIM 3 PHP**, este es un framework que se asemeja a **EXPRESS de NODE.JS**, este además aplica el estándar **PSR**, lo invito a visitar la página oficial de **SLIM**, de hecho, mis pequeñas herramientas para php las creo pensando en el uso con **SLIM**

#### Constructor
por medio del constructor podremos configurar la conexión al servicio **SQL** de nuestro agrado, en este caso **MYSQL**
```php
$tabla_ejemplo = new Simple\Simple([
    "sql"=>"mysql",             // defaul mysql
    "host"=>"localhost",        // defaul localhost
    "user"=>"root",             // defaul root
    "pass"=>"",                 // defaul ""
    "table"=>"ejemplo",         // requerido
    "output"=>"json"            // defaul array
]);
```
#### Método WHERE
permite definir el patrón de búsqueda sea para posteriormente aplicar **select, remove, update**
```php
//{nombre_instancia}->where([ primera_condicion [, parametros OR adicionales] ])
$tabla_ejemplo->where([
    ["name"=>"matias"],
    ["name"=>"jeral"]
])->select()
```
> En el ejemplo anterior se construirá la siguiente consulta **SELECT * FROM ejemplo WHERE (name='matias') OR (name='jeral')**, aparentemente luce mas larga pero las capacidades de manipular el array son muyo mayores que consulta escrita, como lo enseña el ejemplo anterior

```php
$consulta = [
    ["name"=>"matias"]
];
if($_GET["append"]="jeral"){
    $consulta[1]=[
        "name"=>"jeral",
        "edad"=>20
    ];
}elseif($_GET["append"]="octavio"){
    $consulta[1]=[
        "name"=>"octavio",
        "edad"=>22
    ];
}elseif($_GET["append"]="linca"){
    $consulta[1]=[
        "name"=>"linca",
        "edad"=>23
    ];
}else{
    $consulta[1]=[
        "name"=>"ultimo",
        "edad"=>50
    ];
}
$tabla_ejemplo->where($consulta)->select()
```

#### Where reutilizable
a su vez al declarar el método where este retorna una instancia nueva, haciendo que dada elemento definido dentro de where sea único, impidiendo que se reescriba la sentencia y haciéndolo reutilizable
```php
$where_1 = $tabla->where([
    ["name"=>"matias"],
    ["name"=>"jeral"]
]);

$where_2 = $tabla->where([
    ["name"=>"Octavio"],
    ["name"=>"Linca"]
]);

var_export( $where_1->select() ); // retorna los resultados del primer where
var_export( $where_2->select() ); // retorna los resultados del segundo where
echo $where_1->query;
echo $where_2->query;
```
> El ejemplo anterior enseña las condicionantes que definieron el método **WHERE**, estas son habituales en el desarrollo de **API REST**

#### Método SELECT
Este método permite obtener en función del where el contenido y añadir criterios que terminen la consulta.

##### Seleccionando todo
tomara todos los campos de la tabla **SELECT * FROM ejemplo**
```php
$tabla_ejemplo->select()
```
##### Seleccionando partes
Tomara columnas **SELECT 'name','age','sex' FROM ejemplo**
```php
$tabla_ejemplo->select(["name","age","sex"])
```
##### Seleccionando partes y ordenando
Tomara columnas y los ordenara de forma descendente **SELECT 'name' FROM ejemplo ORDER BY age DESC**
```php
$tabla_ejemplo->select(["name"],["age"=>-1])
```
##### Seleccionando partes, ordenando y generando rango sobre la selección
Tomará columnas, las ordenara de forma descendente y de la selección solo tomara desde la primera fila a la tercera **SELECT 'name' FROM ejemplo ORDER BY age DESC LIMIT 1,3**
```php
$tabla_ejemplo->select(["name"],["age"=>-1],[1,3])
```
##### Metodo  Insert
permite añadir nuevos campos a la tabla instanciada
```php
$tabla->insert([
    "name"=>"matias",
    "age" => 26
]);
```
##### Método  Delete
permite añadir todos los campos si no se define **where** o borrar en función de la instancia de **where**
```php
$tabla->delete();
```
##### Funciones SQL
hasta el momento he integrado solo funcionalidad básica a **simple php**:
###### seleccionando por rango
```php
$tabla->where([
    ["age"=>"range(20-30)"]
]);
```
###### seleccionando por conjunto
```php
$tabla->where([
    ["age"=>"in(20,22,24,26)"]
]);
```
###### seleccionando por negación
en este ejemplo se presenta el comodín **?** que apunta directamente al índex definido en el array
```php
$tabla->where([
    ["age"=>"not(?>20)"]
]);
```
###### seleccionando patrón
```php
$tabla->where([
    ["age"=>"like(%m%)"]
]);
```
