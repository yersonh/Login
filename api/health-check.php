<?php
// Esto es suficiente para un healthcheck básico.
// Simplemente devuelve un código de estado 200 OK
// y un cuerpo mínimo.
http_response_code(200);
echo "OK";
?>