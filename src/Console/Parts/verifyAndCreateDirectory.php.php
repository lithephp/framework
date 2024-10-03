<?php

// Function to verify and create the directory if it does not exist

use Symfony\Component\Console\Style\SymfonyStyle;

function verifyAndCreateDirectory($directory, SymfonyStyle $io)
{
    if (!file_exists($directory)) {
        // Create the directory if it does not exist
        mkdir($directory, 0755, true);
    }
}