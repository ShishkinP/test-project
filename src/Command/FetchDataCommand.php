<?php
/**
 * 2019-06-28.
 */

declare(strict_types=1);

namespace App\Command;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class FetchDataCommand.
 */
class FetchDataCommand extends Command
{
    private const SOURCE = 'https://trailers.apple.com/trailers/home/rss/newtrailers.rss';

    /**
     * @var integer
     */
    private $numOfMovies;

    /**
     * @var string
     */
    protected static $defaultName = 'fetch:trailers';

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * FetchDataCommand constructor.
     *
     * @param ClientInterface        $httpClient
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $em
     * @param string|null            $name
     */
    public function __construct(ClientInterface $httpClient, LoggerInterface $logger, EntityManagerInterface $em, string $name = null)
    {
        parent::__construct($name);
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->doctrine = $em;
    }

    public function configure(): void
    {
        $this
            ->setDescription('Fetch data from iTunes Movie Trailers')
            ->addArgument('source', InputArgument::OPTIONAL, 'Overwrite source')
            ->addOption('number','num',InputOption::VALUE_OPTIONAL, 'How many movies will be added', 10)
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info(sprintf('Start %s at %s', __CLASS__, (string) date_create()->format(DATE_ATOM)));


        $source = self::SOURCE;
        if ($input->getArgument('source')) {
            $source = $input->getArgument('source');
        }

        if (!is_string($source)) {
            throw new RuntimeException('Source must be string');
        }

        if (!is_numeric($input->getOption('number'))) {
            throw new RuntimeException('Number must be integer');
        }

        $this->numOfMovies = $input->getOption('number');

        $io = new SymfonyStyle($input, $output);
        $io->title(sprintf('Fetch data from %s', $source));

        try {
            $response = $this->httpClient->sendRequest(new Request('GET', $source));
        } catch (ClientExceptionInterface $e) {
            throw new RuntimeException($e->getMessage());
        }
        if (($status = $response->getStatusCode()) !== 200) {
            throw new RuntimeException(sprintf('Response status is %d, expected %d', $status, 200));
        }
        $data = $response->getBody()->getContents();
        $this->processXml($data);

        $this->logger->info(sprintf('End %s at %s', __CLASS__, (string) date_create()->format(DATE_ATOM)));

        return 0;
    }

    /**
     * @param string $data
     *
     * @throws \Exception
     */
    protected function processXml(string $data): void
    {
        $xml = (new \SimpleXMLElement($data))->children();
        $namespace = $xml->getNamespaces(true)['content'];
        $dom = new \DOMDocument();

        if (!property_exists($xml, 'channel')) {
            throw new RuntimeException('Could not find \'channel\' element in feed');
        }

        $items = $xml->channel->xpath('item');

        for ($i = 0; ($i <= $this->numOfMovies - 1) || (count($items) < $i); $i++) {

            $this->logger->info(sprintf("№: %s",$i+1));

            $item = $items[$i];
            $dom->loadHTML((string) $item->children($namespace)->encoded);
            $image = $dom->getElementsByTagName("img")[0]->getAttribute('src');
            $title = (string) $item->title;
            $trailer = $this->doctrine->getRepository(Movie::class)->getMovieByTitle($title);

            if ($trailer === null) {

                $this->logger->info('Create new Movie', ['title' => $title]);

                $trailer = new Movie();
                $trailer->setTitle((string) $title)
                        ->setImage((string) $image)
                        ->setDescription((string) $item->description)
                        ->setLink((string) $item->link)
                        ->setPubDate($this->parseDate((string) $item->pubDate));
                $this->doctrine->persist($trailer);
            }
            else {
                $this->logger->info('Movie found', ['title' => $title]);
            }
        }
        $this->doctrine->flush();
    }

    /**
     * @param string $date
     *
     * @return \DateTime
     *
     * @throws \Exception
     */
    protected function parseDate(string $date): \DateTime
    {
        return new \DateTime($date);
    }

}
