<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Interfaces\RouteCollectorInterface;
use Twig\Environment;

/**
 * Class HomeController.
 */
class HomeController
{
    /**
     * @var RouteCollectorInterface
     */
    private $routeCollector;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * HomeController constructor.
     *
     * @param RouteCollectorInterface $routeCollector
     * @param Environment             $twig
     * @param EntityManagerInterface  $em
     */
    public function __construct(RouteCollectorInterface $routeCollector, Environment $twig, EntityManagerInterface $em)
    {
        $this->routeCollector = $routeCollector;
        $this->twig = $twig;
        $this->em = $em;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     *
     * @throws HttpBadRequestException
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $this->twig->render('home/index.html.twig', [
                'trailers' => $this->em->getRepository(Movie::class)->getLatestMovies(),
            ]);
        } catch (\Exception $e) {
            throw new HttpBadRequestException($request, $e->getMessage(), $e);
        }

        $response->getBody()->write($data);

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     *
     * @throws HttpBadRequestException
     */
    public function details(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $movieId = $request->getAttribute('movieId');

        try {
            $data = $this->twig->render('home/details.html.twig', [
                'trailer' => $this->em->getRepository(Movie::class)->findOneBy(['id' => $movieId]), //fetchData()[$movieId],
            ]);
        } catch (\Exception $e) {
            throw new HttpBadRequestException($request, $e->getMessage(), $e);
        }

        $response->getBody()->write($data);

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function addLike(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $movieId = $request->getAttribute('movieId');
        $trailer = $this->em->getRepository(Movie::class)->findOneBy(['id' => $movieId]);
        $trailer->setLikes($trailer->getLikes() + 1);
        $this->em->persist($trailer);
        $this->em->flush();
        $payload = json_encode((string) $trailer->getLikes());

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

}
