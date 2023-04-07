<?php

namespace OCA\WorkflowMediaConverter\BackgroundJobs;

interface MediaConversionJob
{
    function parseArguments($arguments);
}