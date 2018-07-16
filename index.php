<?php

try {
    Rnd::error('OVO JE GRESKA!');
} catch (\rnd\base\InvalidConfigException $e) {
    dd($e);
}