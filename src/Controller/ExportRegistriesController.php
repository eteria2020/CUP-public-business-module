<?php

namespace CUPPublicBusinessModule\Controller;

use BusinessCore\Entity\BusinessFleet;
use BusinessCore\Entity\BusinessInvoice;
use BusinessCore\Service\BusinessFleetService;
use BusinessCore\Service\BusinessInvoiceService;
use BusinessCore\Service\BusinessService;
use SharengoCore\Service\SimpleLoggerService as Logger;
use SharengoCore\Service\CustomersService;
use SharengoCore\Service\InvoicesService;
use SharengoCore\Service\FleetService;
use SharengoCore\Service\EmailService;
use SharengoCore\Exception\FleetNotFoundException;

use Zend\Mvc\Controller\AbstractActionController;

class ExportRegistriesController extends AbstractActionController
{
    const TYPE_INVOICES = "Invoices";

    const TYPE_CUSTOMERS = "Customers";

    const TYPE_BUSINESS_INVOICES = "BusinessInvoices";

    const TYPE_BUSINESSES = "Businesses";

    /**
     * @var CustomersService
     */
    private $customersService;

    /**
     * @var InvoicesService
     */
    private $customerInvoicesService;

    /**
     * @var FleetService
     */
    private $fleetService;

    /**
     * @var EmailService
     */
    private $emailService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $exportConfig;

    /**
     * @var array
     */
    private $alertConfig;

    /**
     * Specifies wether files should be written
     * @var boolean
     */
    private $dryRun;

    /**
     * Specifies wether data for customers will be exported
     * @var boolean
     */
    private $noCustomers;

    /**
     * Specifies wether data for businesses will be exported
     * @var boolean
     */
    private $noBusinesses;

    /**
     * Specifies wether data for invoices will be exported
     * @var boolean
     */
    private $noInvoices;

    /**
     * Specifies wether data for all days will be exported
     * @var boolean
     */
    private $all;

    /**
     * Specifies wether ftp connection and upload will be made
     * @var boolean
     */
    private $noFtp;

    /**
     * Specifies prepended text to filenames.
     * @var string
     */
    private $testName;

    /**
     * Connection to ftp server
     * @var resource | null
     */
    private $ftpConn = null;
    /**
     * @var BusinessInvoiceService
     */
    private $businessInvoiceService;
    /**
     * @var BusinessFleetService
     */
    private $businessFleetService;
    /**
     * @var BusinessService
     */
    private $businessService;

    /**
     * @param CustomersService $customersService
     * @param BusinessService $businessService
     * @param InvoicesService $customerInvoiceService
     * @param BusinessInvoiceService $businessInvoiceService
     * @param FleetService $fleetService
     * @param BusinessFleetService $businessFleetService
     * @param EmailService $emailService
     * @param Logger $logger
     * @param array $exportConfig
     * @param array $alertConfig
     */
    public function __construct(
        CustomersService $customersService,
        BusinessService $businessService,
        InvoicesService $customerInvoiceService,
        BusinessInvoiceService $businessInvoiceService,
        FleetService $fleetService,
        BusinessFleetService $businessFleetService,
        EmailService $emailService,
        Logger $logger,
        $exportConfig,
        $alertConfig
    ) {
        $this->customersService = $customersService;
        $this->customerInvoicesService = $customerInvoiceService;
        $this->fleetService = $fleetService;
        $this->emailService = $emailService;
        $this->logger = $logger;
        $this->exportConfig = $exportConfig;
        $this->alertConfig = $alertConfig;
        $this->businessInvoiceService = $businessInvoiceService;
        $this->businessFleetService = $businessFleetService;
        $this->businessService = $businessService;
    }

    /**
     * Available params are:
     *     -d (does not generate files)
     *     -c (does not export customers data)
     *     -i (does not export invoices data)
     *     -a (exports data for all days, overrides --date)
     *     -f (does not connect to ftp)
     *     -t (appends "test-" to filenames)
     *     --date= (export for specified date, date_create formats accepted)
     */
    public function exportRegistriesAction()
    {
        // Setup logger
        $this->logger->setOutputEnvironment(Logger::OUTPUT_ON);
        $this->logger->setOutputType(Logger::TYPE_CONSOLE);

        // Get/Set params
        $request = $this->getRequest();
        $this->dryRun = $request->getParam('dry-run') || $request->getParam('d');
        $this->noCustomers = $request->getParam('no-customers') || $request->getParam('c');
        $this->noBusinesses = $request->getParam('no-businesses') || $request->getParam('b');
        $this->noInvoices = $request->getParam('no-invoices') || $request->getParam('i');
        $this->all = $request->getParam('all') || $request->getParam('a');
        $this->noFtp = $request->getParam('no-ftp') || $request->getParam('f');
        $this->testName = $request->getParam('test-name') || $request->getParam('t') ? 'test-' : '';
        $path = $this->exportConfig['path'];
        $this->logger->log("\nStarted\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");

        // Retrieve customer invoices
        $customerInvoicesByDate = $this->retrieveData();

        // Retrieve business invoices
        $businessInvoicesByDate = $this->retrieveBusinessData();

        // Start the ftp connection and login
        //$this->connectToServer($this->exportConfig);

        foreach ($customerInvoicesByDate as $invoices) {
            $date = $invoices[0]->getDateTimeDate();
            $this->logger->log("\nParsing customers invoices for date: " . $date->format('Y-m-d') . "\n");
            $invoicesEntries = [];
            $customersEntries = [];

            // Generate the data to be exported
            foreach ($invoices as $invoice) {
                $fleetName = $invoice->getFleet()->getName();
                if (!$this->noInvoices) {
                    $this->logger->log("Exporting invoice: " . $invoice->getId() . "\n");
                    if (!array_key_exists($fleetName, $invoicesEntries)) {
                        $invoicesEntries[$fleetName] = '';
                    }
                    $invoicesEntries[$fleetName] .= $this->customerInvoicesService->getExportDataForInvoice($invoice) . "\r\n";
                }
                if (!$this->noCustomers) {
                    $this->logger->log("Exporting customer: " . $invoice->getCustomer()->getId() . "\n");
                    if (!array_key_exists($fleetName, $customersEntries)) {
                        $customersEntries[$fleetName] = '';
                    }
                    $customersEntries[$fleetName] .= $this->customersService->getExportDataForCustomer($invoice->getCustomer()) . "\r\n";
                }
            }

            // Export invoices data
            $this->exportData($date, $invoicesEntries, self::TYPE_INVOICES, $path);

            // Export customers data
            $this->exportData($date, $customersEntries, self::TYPE_CUSTOMERS, $path);
        }

        foreach ($businessInvoicesByDate as $invoices) {
            $date = $invoices[0]->getDateTimeDate();
            $this->logger->log("\nParsing business invoices for date: " . $date->format('Y-m-d') . "\n");
            $businessInvoicesEntries = [];
            $businessEntries = [];

            // Generate the data to be exported
            /** @var BusinessInvoice $invoice */
            foreach ($invoices as $invoice) {
                $fleetName = $invoice->getFleet()->getName();
                if (!$this->noInvoices) {
                    $this->logger->log("Exporting invoice: " . $invoice->getId() . "\n");
                    if (!array_key_exists($fleetName, $businessInvoicesEntries)) {
                        $businessInvoicesEntries[$fleetName] = '';
                    }
                    $businessInvoicesEntries[$fleetName] .= $this->businessInvoiceService->getExportDataForInvoice($invoice) . "\r\n";
                }
                if (!$this->noBusinesses) {
                    $this->logger->log("Exporting business: " . $invoice->getBusiness()->getCode() . "\n");
                    if (!array_key_exists($fleetName, $businessEntries)) {
                        $businessEntries[$fleetName] = '';
                    }
                    $businessEntries[$fleetName] .= $this->businessService->getExportDataForBusiness($invoice->getBusiness()) . "\r\n";
                }
            }

            // Export invoices data
            $this->exportData($date, $businessInvoicesEntries, self::TYPE_BUSINESS_INVOICES, $path);

            // Export business data
            $this->exportData($date, $businessEntries, self::TYPE_BUSINESSES, $path);
        }

        if (!$this->noFtp) {
            ftp_close($this->ftpConn);
        }

        $this->logger->log("Done\ntime = " . date_create()->format('Y-m-d H:i:s') . "\n\n");
    }

    /**
     * Retrieves invoices based on params and groups them as needed
     * @return array[]
     */
    private function retrieveData()
    {
        $this->logger->log("Retrieving customer invoices...");
        $invoices = null;
        $filterFleet = $this->request->getParam('fleet');
        if ($filterFleet !== null) {
            try {
                $filterFleet = $this->fleetService->getFleetByCode($filterFleet);
            } catch(FleetNotFoundException $e) {
                $this->logger->log("\nUse a valid fleet code!\n");
                exit;
            }
        }
        if ($this->all) {
            $this->logger->log("all...");
            $invoices = $this->customerInvoicesService->getInvoicesByFleetJoinCustomers($filterFleet);
        } else {
            $date = date_create($this->request->getParam('date') ?: 'yesterday');
            // validate date
            if ($date === false) {
                $this->logger->log("\nPlease use a valid date format (eg. YYYY-MM-DD)\n");
                exit;
            }
            $this->logger->log("for " . $date->format('Y-m-d') . '...');
            $invoices = $this->customerInvoicesService->getInvoicesByDateAndFleetJoinCustomers($date, $filterFleet);
        }
        $this->logger->log(" Retrieved!\n");
        return $this->customerInvoicesService->groupByInvoiceDate($invoices);
    }

    /**
     * Retrieves invoices based on params and groups them as needed
     * @return array[]
     */
    private function retrieveBusinessData()
    {
        $this->logger->log("Retrieving business invoices...");
        $invoices = null;
        $filterFleet = $this->request->getParam('fleet');
        if ($filterFleet !== null) {
            try {
                $filterFleet = $this->businessFleetService->getFleetByCode($filterFleet);
            } catch(FleetNotFoundException $e) {
                $this->logger->log("\nUse a valid fleet code!\n");
                exit;
            }
        }
        if ($this->all) {
            $this->logger->log("all...");
            $invoices = $this->businessInvoiceService->getInvoicesByFleetJoinBusiness($filterFleet);
        } else {
            $date = date_create($this->request->getParam('date') ?: 'yesterday');
            // validate date
            if ($date === false) {
                $this->logger->log("\nPlease use a valid date format (eg. YYYY-MM-DD)\n");
                exit;
            }
            $this->logger->log("for " . $date->format('Y-m-d') . '...');
            $invoices = $this->businessInvoiceService->getInvoicesByDateAndFleetJoinBusiness($date, $filterFleet);
        }
        $this->logger->log(" Retrieved!\n");
        return $this->businessInvoiceService->groupByInvoiceDate($invoices);
    }

    /**
     * @param \DateTime $date
     * @param string[] $entries
     * @param string $type
     * @param string $path
     */
    private function exportData(\DateTime $date, $entries, $type, $path)
    {
        if (!$this->dryRun && !$this->noInvoices && !empty($entries)) {
            $this->logger->log("Writing " . $type . " to file for the day\n");

            foreach ($entries as $fleetName => $entry) {
                $fileName = $this->testName . "export" . $type . '_' . $date->format('Y-m-d') . ".txt";
                $this->ensurePathExistsLocally($path . $fleetName);
                $file = fopen($path . $fleetName . '/' . $fileName, 'w');
                fwrite($file, $entry);
                fclose($file);

                $this->exportToFtp($path . $fleetName . '/' . $fileName, $fleetName . '/' . $fileName);
            }
        }
    }

    /**
     * Checks wether path exists under data/export and creates it if it doesn't
     * @param string $path
     */
    private function ensurePathExistsLocally($path)
    {
        if (!file_exists($path)) {
            $this->logger->log("Generating local directory " . $path . " ... ");
            if (mkdir($path)) {
                $this->logger->log("Done!\n");
            } else {
                //TODO
                //$this->emailService->sendEmail(
                //    $this->alertConfig['to'],
                //    "Sharengo - export error",
                //    "Error while creating local directory at path " . $path .
                //    " Export was aborted"
                //);
                $this->logger->log("Failed!\n");
                exit;
            }
        }
    }

    /**
     * Params expected to be relative paths like path/to/file/.../filename.txt
     * @param string $from
     * @param string $to
     */
    private function exportToFtp($from, $to)
    {
        //TODO
        return;
        if (!$this->noFtp) {
            if (ftp_put($this->ftpConn, $to, $from, FTP_ASCII)) {
                $this->logger->log("File uploaded successfully\n");
            } else {
                $this->emailService->sendEmail(
                    $this->alertConfig['to'],
                    "Sharengo - export error",
                    "The ftp connection was established but there was an error "
                    . "uploading file " . $from . " to " . $to
                );
                $this->logger->log("Error uploading file\n");
            }
        }
    }

    /**
     * Attempts connection to ftp server
     * @param string[] $config
     */
    private function connectToServer($config)
    {
        //TODO
        return;
        if (!$this->noFtp) {
            $this->logger->log("Connecting to ftp server... ");
            $this->ftpConn = ftp_connect($config['server']);
            if (!$this->ftpConn) {
                $this->emailService->sendEmail(
                    $this->alertConfig['to'],
                    "Sharengo - export error",
                    "The ftp connection could not be established. Date: " .
                    date_create()->format('Y-m-d H:i:s') .
                    " Export was aborted!"
                );
                $this->logger->log(" Could not connect to ftp server! ...aborting export\n");
                die;
            }
            $login = ftp_login($this->ftpConn, $config['name'], $config['password']);
            ftp_pasv($this->ftpConn, true);
            $this->logger->log(" Connected!\n");
        }
    }
}
