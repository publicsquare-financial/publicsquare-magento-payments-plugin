<?php

namespace Symfony\Component\Console\Output;

interface OutputInterface
{
    public function writeln($messages, $options = 0);
}