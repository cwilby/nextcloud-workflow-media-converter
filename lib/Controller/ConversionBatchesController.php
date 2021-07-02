<?php

namespace OCA\WorkflowMediaConverter\Controller;

use OCA\WorkflowMediaConverter\BackgroundJobs\BatchConvertMediaJob;
use OCA\WorkflowMediaConverter\BackgroundJobs\ConvertMediaJob;
use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\BackgroundJob\IJobList;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

class ConversionBatchesController extends Controller
{
    private IJobList $jobList;
    private ConfigService $configService;
    private IL10N $l;
    private IDBConnection $connection;

    public function __construct($AppName, IRequest $request, IJobList $jobList, ConfigService $configService, IL10N $l, IDBConnection $connection, IUserSession $session)
    {
        parent::__construct($AppName, $request);
        $this->userId = $session->getUser()->getUID();
        $this->jobList = $jobList;
        $this->configService = $configService;
        $this->l = $l;
        $this->connection = $connection;
    }

    public function index(): DataResponse
    {
        return new DataResponse($this->getConversionBatches());
    }

    public function show($id): DataResponse
    {
        $conversionBatches = $this->getConversionBatches();

        $index = array_search($id, array_column($conversionBatches, 'id'));

        if (!isset($conversionBatches[$index])) {
            return new DataResponse(null, Http::STATUS_NOT_FOUND);
        }

        return new DataResponse($conversionBatches[$index]);
    }

    public function create(array $batch): DataResponse
    {
        $conversionBatches = $this->getConversionBatches();

        if (($error = $this->batchCanBeCreated($batch, $conversionBatches))) {
            return new DataResponse($error, Http::STATUS_BAD_REQUEST);
        }

        $batch['uid'] = $this->userId;

        $this->jobList->add(BatchConvertMediaJob::class, $batch);

        $conversionBatches[] = $batch;

        $this->setConversionBatches($conversionBatches);

        return new DataResponse($batch, Http::STATUS_CREATED);
    }

    public function delete($id): DataResponse
    {
        $this->cancelPendingConversionsForBatch($id);

        $conversionBatches = $this->getConversionBatches();

        $conversionBatches = array_filter(
            $conversionBatches,
            function ($conversionBatch) use ($id) {
                return $conversionBatch['id'] !== $id;
            }
        );

        $this->setConversionBatches($conversionBatches);

        return new DataResponse();
    }

    //

    private function batchCanBeCreated(array $batch, $conversionBatches)
    {
        foreach ($conversionBatches as $conversionBatch) {
            if (
                $conversionBatch['sourceFolder'] === $batch['sourceFolder']
                && $conversionBatch['convertMediaInSubFolders'] === $batch['convertMediaInSubFolders']
                && $conversionBatch['sourceExtension'] === $batch['sourceExtension']
                && $conversionBatch['outputExtension'] === $batch['outputExtension']
            ) {
                return $this->l->t('A similar conversion batch has already been created.  Delete that batch and try again');
            }
        }
    }

    private function getConversionBatches()
    {
        return $this->configService->getConfigValueJson('conversionBatches');
    }

    private function setConversionBatches($conversionBatches)
    {
        $this->configService->setConfigValueJson('conversionBatches', $conversionBatches);
    }

    private function cancelPendingConversionsForBatch($id)
    {
        $query = $this->connection->getQueryBuilder();

        $query
            ->delete('jobs')
            ->where($query->expr()->eq('class', $query->createNamedParameter(ConvertMediaJob::class)))
            ->andWhere(
                $query->expr()->like(
                    'argument',
                    $query->createNamedParameter('%' . $this->connection->escapeLikeParameter($id) . '%')
                )
            )
            ->execute();

        $query = $this->connection->getQueryBuilder();
        $query
            ->delete('jobs')
            ->where($query->expr()->eq('class', $query->createNamedParameter(BatchConvertMediaJob::class)))
            ->andWhere(
                $query->expr()->like(
                    'argument',
                    $query->createNamedParameter('%' . $this->connection->escapeLikeParameter($id) . '%')
                )
            )
            ->execute();
    }
}
