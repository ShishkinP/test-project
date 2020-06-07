<?php
/**
 * 2019-06-28.
 */

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Movie;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Class MovieRepository.
 */
class MovieRepository extends EntityRepository
{

    /**
     * @param string $title
     * @return Movie
     */
    public function getMovieByTitle(string $title): Movie
    {
        $item = $this->getEntityManager()->getRepository(Movie::class)->findOneBy(['title' => $title]);

        if (!($item instanceof Movie)) {
            throw new RuntimeException('Wrong type!');
        }

        return $item;
    }

    /**
     * @param $num
     * @return Collection
     */
    public function getLatestMovies(int $num = 10): Collection
    {
        $data = $this->getEntityManager()->getRepository(Movie::class)
            ->findBy([],['pubDate' => 'desc'],$num);

        return new ArrayCollection($data);
    }


    /**
     * @param int $id
     * @return Movie
     */
    public function getMovieById(int $id): Movie
    {
        $item =  $this->getEntityManager()->getRepository(Movie::class)->findOneBy(['id' => $id]);

        if (!($item instanceof Movie)) {
            throw new RuntimeException('Wrong type!');
        }

        return $item;
    }

}
