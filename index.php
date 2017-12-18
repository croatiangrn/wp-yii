<?php

use rnd\requirements\RndRequirementsChecker;

$reqs = new RndRequirementsChecker();
$requirements = [
];

$reqs->checkYii()->render();