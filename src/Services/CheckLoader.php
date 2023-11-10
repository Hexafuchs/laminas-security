<?php

namespace Hexafuchs\LaminasSecurity\Services;

use Hexafuchs\LaminasSecurity\Checks\AbstractCheck;
use Hexafuchs\LaminasSecurity\Classes\Audit;
use Hexafuchs\LaminasSecurity\Classes\Report;
use Hexafuchs\LaminasSecurity\Exceptions\InvalidCheckException;
use Hexafuchs\LaminasSecurity\Exceptions\UnknownAuditException;
use Hexafuchs\LaminasSecurity\Exceptions\UnknownReportException;

class CheckLoader
{
    /**
     * @var array<string, string[]> $auditMapping
     */
    private array $auditMapping;

    /**
     * @var array<string, \Hexafuchs\LaminasSecurity\Checks\AbstractCheck[]> $checkMapping
     */
    private array $checkMapping;

    /**
     * @param array $config
     * @param callable $builder
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\InvalidCheckException
     */
    public function __construct(array $config, callable $builder) {
        $this->auditMapping = $config['audits'];
        $this->checkMapping = [];

        foreach ($config['checks'] as $checkClassName) {
            $check = $builder($checkClassName);

            if (!$check instanceof AbstractCheck) {
                throw new InvalidCheckException($check::class);
            }

            $this->checkMapping[$check->getReportName()][] = $check;
        }
    }

    /**
     * Returns a list of names of all registered reports
     *
     * @return string[]
     */
    public function getReportNames(): array
    {
        return array_keys($this->checkMapping);
    }

    /**
     * Returns a list of names of all registered audits
     *
     * @return string[]
     */
    public function getAuditNames(): array
    {
        return array_keys($this->auditMapping);
    }

    /**
     * Returns all checks which belong to the given report-name
     *
     * @param string $reportName
     * @return \Hexafuchs\LaminasSecurity\Checks\AbstractCheck[]
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\UnknownReportException
     */
    public function getChecksByReportName(string $reportName): array
    {
        if (!array_key_exists($reportName, $this->checkMapping)) {
            throw new UnknownReportException($reportName, $this->getReportNames());
        }

        return $this->checkMapping[$reportName];
    }

    /**
     * Returns all Reports which belong to the given audit-name
     *
     * @param string $auditName
     * @return array
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\UnknownAuditException
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\UnknownReportException
     */
    public function getReportsByAuditName(string $auditName): array
    {
        if (!array_key_exists($auditName, $this->auditMapping)) {
            throw new UnknownAuditException($auditName, $this->getAuditNames());
        }

        $reports = [];

        foreach ($this->auditMapping[$auditName] as $reportName) {
            $reports[] = $this->createReport($reportName);
        }

        return $reports;
    }

    /**
     * Creates and returns a report with the given name and fills it with the checks that belong to it.
     *
     * @param string $reportName
     * @return \Hexafuchs\LaminasSecurity\Classes\Report
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\UnknownReportException
     */
    public function createReport(string $reportName): Report
    {
        return new Report($reportName, $this->getChecksByReportName($reportName));
    }

    /**
     * Creates and returns an audit with the given name and fills it with the reports that belong to it.
     *
     * @param string|null $auditName
     * @return \Hexafuchs\LaminasSecurity\Classes\Audit
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\UnknownAuditException
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\UnknownReportException
     */
    public function createAudit(?string $auditName): Audit
    {
        return $auditName === null
            ? $this->createFullAudit()
            : new Audit($auditName, $this->getReportsByAuditName($auditName));
    }

    /**
     * Creates an audit containing all known reports.
     *
     * @return \Hexafuchs\LaminasSecurity\Classes\Audit
     * @throws \Hexafuchs\LaminasSecurity\Exceptions\UnknownReportException
     */
    private function createFullAudit(): Audit
    {
        $reports = [];

        foreach ($this->getReportNames() as $reportName) {
            $reports[] = $this->createReport($reportName);
        }

        return new Audit('full', $reports);
    }
}