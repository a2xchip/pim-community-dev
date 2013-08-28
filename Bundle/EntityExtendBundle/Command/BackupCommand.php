<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class BackupCommand extends ContainerAwareCommand
{
    /**
     * @var \Oro\Bundle\EntityExtendBundle\Databases\DatabaseInterface
     */
    protected $database;

    /** @var  string backup folder path */
    protected $basePath;

    /** @var  string backup filename */
    protected $fileName;

    /** @var  string backup path + filename */
    protected $filePath;

    /** @var  string Entity class name */
    protected $entity;

    /**
     * Console command configuration
     */
    protected function configure()
    {
        $this
            ->setName('oro:entity-extend:backup')
            ->setDescription('Backup database table(s)')
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity class name (REQUIRED)')
            ->addArgument('path', InputArgument::OPTIONAL, 'Override configured backup path');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $parameters = $this->getContainer()->getParameterBag();

        $this->basePath = $input->getArgument('path') ?: $parameters->get('oro_entity_extend.backup');
        $this->entity   = $input->getArgument('entity');

        if (!$this->entity) {
            return;
        }

        $dbms      = $parameters->get('database_driver');
        $database  = $parameters->get('database_name');
        $user      = $parameters->get('database_user');
        $password  = $parameters->get('database_password');
        $host      = $parameters->get('database_host');

        $tables    = array();
        //$tables    = array('oro_config_entity', 'oro_config_field');

        /** @var ConfigProvider $extendConfigProvider */
        $extendConfigProvider = $this->getContainer()->get('oro_entity_config.provider.extend');

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager('default');

        $tables[] = $em
            ->getClassMetadata($extendConfigProvider->getConfig($this->entity)->get('extend_class'))
            ->getTableName();

        switch ($dbms) {
            case 'pdo_mysql':
                $this->database = new \Oro\Bundle\EntityExtendBundle\Databases\MySQLDatabase(
                    $database,
                    $user,
                    $password,
                    $tables,
                    $host
                );
                break;
            case 'postgresql':
                //$this->database = new PostgresDatabase();
                break;
        }

        $this->fileName = date('Y-m-d_H-i-s') . '.' . $this->database->getFileExtension();
        $this->filePath = $this->basePath . $this->fileName;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Start backup');

        if (!is_dir($this->basePath)) {
            mkdir($this->basePath);
        }

        system($this->database->dump($this->filePath));

        if (file_exists($this->filePath) && filesize($this->filePath) > 0) {
            $output->writeln(
                sprintf(
                    'Database backup was successful. %s was saved in the dumps folder.',
                    $this->fileName
                )
            );
        } else {
            $output->writeln('Database backup failed');
        }

        $output->writeln('Done');
    }
}
