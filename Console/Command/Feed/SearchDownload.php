<?php
/**
 * Copyright (c) 2020 Unbxd Inc.
 */

/**
 * Init development:
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace Unbxd\ProductFeed\Console\Command\Feed;

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\Store;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\State;

class SearchDownload extends Command
{
    const COMMAND = 'unbxd:search-data:download';
    const DIR_FOR_DOWNLOAD = 'unbxd/download/search/';
    const SEARCH_PATH_GENERATED_FOR_DOWNLOAD = 'unbxd_search/data/generated_for_download';
    const STORE_INPUT_OPTION_KEY = 'store';
    const STORE_PARAMETER = '_store';
    private $state;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;


    public function __construct(
        ObjectManagerInterface $objectManager,
        \Magento\Framework\Filesystem $filesystem
        
    )
    {
        $this->objectManager = $objectManager;
        parent::__construct();
	}



    protected function configure()
    {
        $this->setName(self::COMMAND)
        ->setDescription('Generate full Search data download.')
        ->addOption(
            self::STORE_INPUT_OPTION_KEY,
            's',
            InputOption::VALUE_REQUIRED,
            'Use the specific Store View',
            Store::DEFAULT_STORE_ID
        );
        parent::configure();
    }

    private function getState(){
        if (!$this->state){
            $this->state = $this->objectManager->get(\Magento\Framework\App\State::class);
        }
        return $this->state;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->getState()->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
            $storeId = $input->getOption(self::STORE_INPUT_OPTION_KEY);
            if (!$storeId) {
                $storeId = $this->getDefaultStoreId();
            }
            
            $filesystem = $this->objectManager->get(\Magento\Framework\Filesystem::class);
            $directory  =  $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $resource   = $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();;
            $search_result 	  = $connection->select()->from(['sq' => 'search_query'],
                ['query_text','num_results','popularity', 'updated_at']
            )->where('store_id = ?', $storeId);
            $search_data = $connection->fetchAll($search_result);
            $filepath =  self::DIR_FOR_DOWNLOAD.'search_data'.self::STORE_PARAMETER.$storeId.'.csv';
            $directory->create('export');
            $stream = $directory->openFile($filepath, 'w+');
            $stream->lock();
            $header = ['Query Text', 'ResultCount', 'Popularity','Updated_at'];
            $stream->writeCsv($header);
            if (!empty($search_data)) {
                foreach ($search_data as $result) {
                    $data    = [];
                    $data [] =  $result['query_text'];
                    $data [] =  $result['num_results'];
                    $data [] =  $result['popularity'];
                    $data [] =  $result['updated_at'];
                    $stream->writeCsv($data);
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {

        }
        return 0;

    }

    protected function getDefaultStoreId()
    {
        $storeManager  = $this->objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
        return $storeManager->getStore()->getId();
    }
}
