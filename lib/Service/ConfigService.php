<?php

namespace OCA\WorkflowMediaConverter\Service;

use OCA\WorkflowMediaConverter\AppInfo\Application;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class ConfigService
{
    private $userId;
    private IConfig $config;
    private LoggerInterface $logger;

    public function __construct($userId, IConfig $config, LoggerInterface $logger)
    {
        $this->userId = $userId;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function setUserId($id)
    {
        $this->userId = $id;
    }

    public function getCurrentUserConfig()
    {
        return [
            'conversionBatches' => $this->getConfigValueJson('conversionBatches', '[]')
        ];
    }

    public function getCurrentUserStatistics()
    {
        return [];
    }

    public function getAdminConfig()
    {
        return [
            'threadLimit' => $this->getAppConfigValue('threadLimit', 0),
            'maxThreads' => $this->getMaxThreads()
        ];
    }

    public function getConfigValue($key, $default = '')
    {
        return $this->config->getUserValue($this->userId, Application::APP_ID, $key, $default);
    }

    public function getConfigValueJson($key, $default = '[]')
    {
        return json_decode($this->getConfigValue($key, $default), true);
    }

    public function setConfig($values)
    {
        $this->setConfigValueJson('conversionBatches', $values['conversionBatches']);
    }

    public function setConfigValue($key, $value)
    {
        $this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
    }

    public function setConfigValueJson($key, $value = [])
    {
        $this->setConfigValue($key, json_encode($value));
    }

    public function getAppConfigValue($key, $default = '')
    {
        return $this->config->getAppValue(Application::APP_ID, $key, $default);
    }

    public function getAppConfigValueJson($key, $default = '[]')
    {
        return json_decode($this->getAppConfigValue($key, $default), true);
    }

    public function setAppConfigValue($key, $value)
    {
        $this->config->setAppValue(Application::APP_ID, $key, $value);
    }

    public function setAppConfigValueJson($key, $value = [])
    {
        $this->setAppConfigValue($key, json_encode($value));
    }

    public function setAppConfig($values)
    {
        $this->setAppConfigValue('threadLimit', $values['threadLimit']);
    }

    public function addToCounters($counter, $amount)
    {
        $this->addToAdminCounter($counter, $amount);
        $this->addToCounter($counter, $amount);
    }

    public function addToAdminCounter($counter, $amount)
    {
        $this->setAppConfigValue($counter, $this->getAppConfigValue($counter, '0') + $amount);
    }

    public function addToCounter($counter, $amount)
    {
        $this->setConfigValue($counter, $this->getConfigValue($counter, '0') + $amount);
    }

    public function getConversionRules()
    {
        return json_decode($this->getConfigValue("video_conversion_rules", '[]'), true);
    }

    public function getBatch($id)
    {
        $batches = $this->getConfigValueJson('batchConversions');

        $index = array_search($id, array_column($batches, 'id'));

        if (isset($batches[$index])) {
            return $batches[$index];
        }

        return null;
    }

    public function updateBatch($id, $changes)
    {
        $batches = $this->getConfigValueJson('batchConversions');

        $index = array_search($id, array_column($batches, 'id'));

        if (isset($batches[$index])) {
            $batch = $batches[$index];
            foreach ($changes as $key => $value) {
                $batch[$key] = $value;
            }
        }

        $this->configService->setConfigValueJson('batchConversions', $batches);
    }

    public function getQueueCount($queueName)
    {
        return count($this->getAppConfigValueJson($queueName));
    }

    private function getMaxThreads()
    {
        try {
            $numCpus = 4;

            if (is_file('/proc/cpuinfo')) {
                $cpuinfo = file_get_contents('/proc/cpuinfo');
                preg_match_all('/^processor/m', $cpuinfo, $matches);
                $numCpus = count($matches[0]);
            } else if ('WIN' == strtoupper(substr(PHP_OS, 0, 3))) {
                $process = @popen('wmic cpu get NumberOfCores', 'rb');
                if (false !== $process) {
                    fgets($process);
                    $numCpus = intval(fgets($process));
                    pclose($process);
                }
            } else {
                $process = @popen('sysctl -a', 'rb');
                if (false !== $process) {
                    $output = stream_get_contents($process);
                    preg_match('/hw.ncpu: (\d+)/', $output, $matches);
                    if ($matches) {
                        $numCpus = intval($matches[1][0]);
                    }
                    pclose($process);
                }
            }

            return $numCpus;
        } catch (\Throwable $e) {
            $this->logger->info($e->getMessage());

            return 4;
        } finally {
            try {
                pclose($process);
            } catch (\Throwable $e) {
                //
            }
        }
    }
}
