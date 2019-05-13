<?php 

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use App\Kernel;
use App\Configurators\BundleConfigurator;
use Symfony\Component\Console\Input\InputOption;

class CreateBundleCommand extends Command
{
    protected static $defaultName = 'bundle:create';



    public function __construct(bool $requirePassword = false)
    {
        $this->requirePassword = $requirePassword;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Create a new Bundle');
        $this->setHelp('This command help you to create a new Bundle in Symfony 4.');
        
        $this->addArgument('organization', InputArgument::REQUIRED,'Name of your organization');
        $this->addArgument('bundleName', InputArgument::REQUIRED, 'Name of Bundle');
        
        $this->addOption('activeBundle', "a", InputOption::VALUE_OPTIONAL, "Active Bundle in current Project", true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundleFolder = "src/" . $input->getArgument('organization') . "/" . $input->getArgument('bundleName');
        $structure = [
            "/Controller",
            "/Command",
            "/Entity",
            "/EventListener",
            "/Resources/config",
            "/Resources/views",
            "/Resources/public",
            "/Resources/translations/",
            "/Resources/config/validation/",
            "/Resources/config/serialization/",
            "/Tests"
        ];
        
        for($i = 0; $i < \count($structure); $i++)
        {
            $output->writeln("creation of $bundleFolder$structure[$i]");
            try
            {
                \mkdir($bundleFolder . $structure[$i], 0775, true);
            }
            catch(\Exception $e)
            {
                $output->writeln("Folder \"$bundleFolder$structure[$i]\" is already exist");
            }
        }
   
        $this->createBundlePhpFile($bundleFolder, $input, $output);
        
        if($input->getOption('activeBundle'))
        {
            $output->writeln("Liste des Bundles");
            dump($this->activeBundle($input));
            die();
        }

        $output->writeln("Command done");
    }

    private function createBundlePhpFile(string $folder, InputInterface $input, OutputInterface $output)
    {
        try
        {
            $file = fopen($folder ."/" . $input->getArgument('bundleName').".php", 'x+');
            $namespace = 'namespace App\\' . $input->getArgument('organization') . "\\" . $input->getArgument('bundleName') . ";";
            $useStatement = "use Symfony\Component\HttpKernel\Bundle\Bundle;";
            $classText = "class " . $input->getArgument('bundleName') . " extends " . "Bundle \n{\n\n}";

            $content = "<?php \n\n" . $namespace . "\n\n" . $useStatement . "\n\n" . $classText;
            fwrite($file, $content);
        }
        catch(\Exception $e)
        {
            $output->writeln("Bundle declaration already exist.");
        }
    }

    private function activeBundle(InputInterface $input)
    {
        $file = "config/bundles.php";
        $bundles = file_exists($file) ? (require $file) : [];
        $bundles = array_merge($bundles, ['App\\' . $input->getArgument('organization') . "\\" . $input->getArgument('bundleName') . "\\" . $input->getArgument('bundleName') => [$input->getOption('env')=>true]]);
        $content = "<?php\n\nreturn [\n";
        foreach($bundles as $bundle => $envs)
        {
            dump($bundle, $envs);
            $counter = 0;
            $content .= "   " . $bundle."::class => [";
            foreach($envs as $env => $envValue)
            {
                if ($envValue)
                {
                    $envValue = "true";
                }
                $content .= "'$env' => $envValue";
                if( !$counter == count( $envs ) - 1) { 
                    $content .= ", ";
                }
                $counter ++;
            }
            $content .= "],\n";
        }
        
        
        $content .= "];\n";
        $bundleFile = \fopen($file, "w");
        \fwrite($bundleFile, $content);
        dump($content);
    }
}