<?php

namespace OCA\WorkflowMediaConverter\Check;

use OC\Files\Storage\Local;
use OCA\WorkflowEngine\Check\AbstractStringCheck;
use OCA\WorkflowEngine\Check\TFileCheck;
use OCA\WorkflowEngine\Entity\File;
use OCP\Files\Mount\IMountManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\WorkflowEngine\IFileCheck;
use PhpParser\Node\Expr\Cast\String_;

class FileExtension extends AbstractStringCheck implements IFileCheck
{
    use TFileCheck;

    protected IRequest $request;

    private IMountManager $mountManager;

    public function __construct(IL10N $l, IRequest $request, IMountManager $mountManager)
    {
        parent::__construct($l);
        $this->request = $request;
        $this->mountManager = $mountManager;
    }

    protected function getActualValue(): string
    {
        $filename = $this->getFileName();

        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    protected function getFileName(): string
    {
        $fileName = $this->path === null ? '' : basename($this->path);

        if ($fileName === '' && (!$this->storage->isLocal() || $this->storage->instanceOfStorage(Local::class))) {
            // Return the mountpoint name of external storage that are not mounted as user home
            $mountPoints = $this->mountManager->findByStorageId($this->storage->getId());
            if (empty($mountPoints) || $mountPoints[0]->getMountType() !== 'external') {
                return $fileName;
            }
            $mountPointPath = rtrim($mountPoints[0]->getMountPoint(), '/');
            $mountPointPieces = explode('/', $mountPointPath);
            $mountPointName = array_pop($mountPointPieces);
            if (!empty($mountPointName) && $mountPointName !== 'files' && count($mountPointPieces) !== 2) {
                return $mountPointName;
            }
        }

        return $fileName;
    }

    protected function executeStringCheck($operator, $checkValue, $actualValue): bool
    {
        if ($operator === 'is' || $operator === '!is') {
            $checkValue = mb_strtolower($checkValue);
            $actualValue = mb_strtolower($actualValue);
        }
        return parent::executeStringCheck($operator, $checkValue, $actualValue);
    }

    public function supportedEntities(): array
    {
        return [File::class];
    }

    public function isAvailableForScope(int $scope): bool
    {
        return true;
    }
}
