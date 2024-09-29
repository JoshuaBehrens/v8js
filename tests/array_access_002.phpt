--TEST--
Test V8::executeString() : Use ArrayAccess with JavaScript native push method
--SKIPIF--
<?php require_once(dirname(__FILE__) . '/skipif.inc');

if (str_starts_with(V8Js::V8_VERSION, '11.3.244.8')) {
    die("skip V8 version known to call setter twice");
}

?>
--INI--
v8js.use_array_access = 1
--FILE--
<?php
#[AllowDynamicProperties]
class MyArray implements ArrayAccess, Countable {
    private $data = Array('one', 'two', 'three');

    public function offsetExists($offset): bool {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->data[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        echo "set[$offset] = $value\n";
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        throw new Exception('Not implemented');
    }

    public function count(): int {
        return count($this->data);
    }
}

$v8 = new V8Js();
$v8->myarr = new MyArray();

/* Call native JavaScript push method, this should cause a count
 * offsetSet call. */
$v8->executeString('PHP.myarr.push(23);');

/* Access array from PHP side, should work if JavaScript called
 * the write accessor functions. */
var_dump(count($v8->myarr));
var_dump($v8->myarr[3]);

/* And JS side of course should see it too. */
$v8->executeString('var_dump(PHP.myarr.join(","));');

?>
===EOF===
--EXPECT--
set[3] = 23
int(4)
int(23)
string(16) "one,two,three,23"
===EOF===
