<?php

namespace App\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;


class RepositoryMaker extends AbstractMaker
{

    private array $_entityNames;
    private string $_entityDir;
    private array $_repositoryNames;
    private string $_repositoryDir;
    private QuestionHelper $_questionHelper;

    public function __construct()
    {
        $this->_questionHelper = new QuestionHelper();
        $this->_entityNames = array();
        //remove this directory name as part of the directory first
        $srcPos = strpos(__DIR__, 'src/');
        $dirSub = substr(__DIR__, 0, $srcPos);
        $this->_entityDir = $dirSub . 'src/Entity/';
        $this->_repositoryDir = $dirSub . 'src/Repository/';
        $this->_repositoryNames = array();
        $this->setFileNames();
    }

    public static function getCommandName(): string
    {
        return 'make:repository';
    }

    public function getCommandDescription(): string
    {
        return 'Creates a basic Doctrine Repository class.';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $words = 'Would you like to loop through a list of entities and create repos or name each one? <fg=yellow>%s</>) ';
        $question = sprintf($words, '( loop or name )');
        $command->setDescription('Creates basic Doctrine Repositories')
            ->addArgument('create-style', InputOption::VALUE_REQUIRED, $question);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        // TODO: Implement configureDependencies() method.
    }

    private function createRepository(string $repositoryName):bool
    {
        $results = false;
        if (empty($this->repositoryExists($repositoryName))) {

            $repoCode = $this->getRepositoryTemplate();
            $file = $this->_repositoryDir . $repositoryName . 'Repository.php';
            $repoText = str_replace('repo_name', $repositoryName, $repoCode);
            $results = file_put_contents($file, $repoText);
            if ($results) {
                $this->_repositoryNames[] = $repositoryName;
            }
        }
        return $results;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $createStyle = $input->getArgument('create-style');

        if (strtolower($createStyle) === 'loop') {
        $repos =  array_diff($this->_entityNames,$this->getRepositoryNames());
            foreach ($repos as $fileName) {
                $io->writeln(' ');
                $io->writeln('Hit ctrl + c to exit this maker.');
                $confirmQuestion = new ConfirmationQuestion('Create a repository for Entity ' . $fileName . ' (yes/no) ?', true);
                $confirmAnswer = $this->_questionHelper->ask($input, $io, $confirmQuestion);

                if ($confirmAnswer) {

                    if ($this->repositoryExists($fileName)) {
                        $this->writeFailure($io, $fileName, 'exists');
                    } else {
                        //now create the repo
                        $this->createRepository($fileName);
                        $this->writeSuccess($io, $fileName);
                    }
                } else {
                    $this->writeFailure($io, $fileName, 'skip');
                }
            }
        } else {
            $io->writeln('Hit ctrl + c to exit or hit enter with no answer for Entity name.');
            $repositoryNameQuestion = new Question('Enter name of Entity to create a Repository for --> ');
            $repositoryNameQuestion->setAutocompleterValues($this->_entityNames);
            $repositoryNameAnswer = $this->_questionHelper->ask($input, $io, $repositoryNameQuestion);

            //check to see if the Entity name the user entered even exists
            while (!empty($repositoryNameAnswer)) {

                if (empty($this->repositoryExists($repositoryNameAnswer))) {
                    if ($this->entityExists($repositoryNameAnswer)) {
                        $this->createRepository($repositoryNameAnswer);
                    } else {
                        $confirmCreateRepoQuestion = new ConfirmationQuestion('An Entity does not exist for' .
                            $repositoryNameAnswer . ' create a repository anyways? ', false);
                        $confirmCreateRepoAnswer = $this->_questionHelper->ask($input, $io, $confirmCreateRepoQuestion);

                        if ($confirmCreateRepoAnswer) {
                            $this->createRepository($repositoryNameAnswer);
                            $this->writeSuccess($io, $repositoryNameAnswer);
                        } else {
                            $this->writeFailure($io, $repositoryNameAnswer, 'skip');
                        }
                    }
                } else {
                    $io->writeln('');
                    $this->writeFailure($io, $repositoryNameAnswer, 'exists');
                }
                //ask the confirmQuestion again
                $repositoryNameAnswer = $this->_questionHelper->ask($input, $io, $repositoryNameQuestion);
                $repositoryNameQuestion->setAutocompleterValues($this->_entityNames);
            }
            $this->writeFailure($io, '', 'no-name');
        }
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement @method string getCommandDescription()
    }

    private function getRepositoryNames(): array{
        return array_map(function ($val){
            return str_replace('Repository', '', $val);
        }, $this->_repositoryNames);
    }
    private function getRepositoryTemplate(): string
    {
        return <<<'TEMPLATE'
<?php

namespace App\Repository;

use App\Entity\repo_name;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * These methods implemented in ServiceEntityRepository extended below
 * @method repo_name|null find($id, $lockMode = null, $lockVersion = null)
 * @method repo_name|null findOneBy(array $criteria, array $orderBy = null)
 * @method repo_name[]    findAll()
 * @method repo_name[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class repo_nameRepository extends ServiceEntityRepository 
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, repo_name::class);
    } 
}
TEMPLATE;
    }

    private function entityExists(string $entityName): bool
    {
        return in_array($entityName, $this->_entityNames);
    }

    private function repositoryExists(string $repositoryName): bool
    {
        return in_array($repositoryName . 'Repository', $this->_repositoryNames);
    }

    private function setFileNames()
    {
        //set teh entities
        $entities = $this->_entityDir . '*';
        $entityFiles = glob($entities);

        foreach ($entityFiles as $file) {
            $fileName = basename($file);
            $this->_entityNames[] = substr($fileName, 0, -4);
        }

        //set the existing repositories
        $repositories = $this->_repositoryDir . '*';
        $repoFiles = glob($repositories);

        foreach ($repoFiles as $repoFile) {
            $repoName = basename($repoFile);
            $this->_repositoryNames[] = substr($repoName, 0, -4);
        }

    }

    private function writeFailure(ConsoleStyle $io, string $repositoryName, string $failReason)
    {

        switch ($failReason) {
            case 'exists':
                $io->writeln('Sorry that Repository ' . $repositoryName . ' exists. Try again.');
                break;

            case 'no-name':
                $io->writeln('No name for repository entered. Exiting now.');
                break;

            case 'skip':
                $io->writeln('Ok skipping ' . $repositoryName . ' file will not be created.');
                break;
        }
        $io->writeln(' ');
    }

    private function writeSuccess(ConsoleStyle $io, string $repositoryName)
    {
        $io->writeln('Success. repository ' . $repositoryName . ' file was created.');
        $io->writeln(' ');
    }
}