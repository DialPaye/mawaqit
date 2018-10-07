<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Mosque;
use AppBundle\Entity\User;

/**
 * MosqueRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MosqueRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param User $user
     * @param array $search
     * @return \Doctrine\ORM\QueryBuilder
     */
    function search(User $user, array $search)
    {
        $qb = $this->createQueryBuilder("m")
            ->leftJoin("m.user", "u", "m.user_id = u.id");

        if (!empty($search)) {
            if (!empty($search["word"])) {
                $qb->where("m.name LIKE :word "
                    . "OR m.associationName LIKE :word "
                    . "OR m.email LIKE :word "
                    . "OR m.address LIKE :word "
                    . "OR m.zipcode LIKE :word "
                    . "OR m.city LIKE :word "
                    . "OR u.username LIKE :word "
                    . "OR u.email LIKE :word"
                )->setParameter(":word", "%" . trim($search["word"]) . "%");
            }


            if (!empty($search["id"])) {
                $qb->andWhere("m.id = :id")
                    ->setParameter(":id", trim($search["id"]));
            }

            if (!empty($search["status"])) {
                $qb->andWhere("m.status = :status")
                    ->setParameter(":status", $search["status"]);
            }

            if (!empty($search["sourceCalcul"])) {
                $qb->innerJoin("m.configuration", "c")
                    ->andWhere("c.sourceCalcul = :sourceCalcul")
                    ->setParameter(":sourceCalcul", $search["sourceCalcul"]);
            }

            if (!empty($search["type"]) && $search["type"] !== 'ALL') {
                $qb->andWhere("m.type = :type")
                    ->setParameter(":type", $search["type"]);
            }

            if (!empty($search["department"])) {
                $qb->andWhere("m.zipcode LIKE :zipcode")
                    ->setParameter(":zipcode", trim($search["department"]) . "%");
            }

            if (!empty($search["country"])) {
                $qb->andWhere("m.country = :country")
                    ->setParameter(":country", $search["country"]);
            }

            if (!empty($search["city"])) {
                $qb->andWhere("m.city = :city")
                    ->setParameter(":city", $search["city"]);
            }
        }

        if (!empty($search["userId"])) {
            $qb->andWhere("m.user = :userId")
                ->setParameter(":userId", $search["userId"]);
        }

        // By default not show homes for admin user
        if (empty($search["userId"]) && $user->getEmail() === "fb.hp.mawaqit@gmail.com" && empty($search["type"])) {
            $qb->andWhere("m.type = :type")
                ->setParameter(":type", "mosque");
        }

        if (!$user->isAdmin()) {
            $qb->andWhere("u.id = :userId")
                ->setParameter(":userId", $user->getId());
        }

        $qb->orderBy("m.created", "DESC");

        return $qb;
    }


    /**
     * @param string $query
     * @return \Doctrine\ORM\QueryBuilder
     */
    function publicSearch($query)
    {
        $qb = $this->createQueryBuilder("m");
        if (!empty($query)) {

            $query = preg_split("/\s+/", trim($query));

            $qb->where("m.type = 'mosque'")
                ->andWhere("m.status = :status")
                ->setParameter(':status', Mosque::STATUS_VALIDATED);

            foreach ($query as $key => $keyword) {
                $qb->andwhere("m.name LIKE :keyword$key "
                    . "OR m.associationName LIKE :keyword$key "
                    . "OR m.address LIKE :keyword$key "
                    . "OR m.city LIKE :keyword$key "
                    . "OR m.zipcode LIKE :keyword$key "
                    . "OR m.country LIKE :keyword$key "
                )->setParameter(":keyword$key", "%$keyword%");
            }
        }

        return $qb;
    }


    /**
     * get configured mosques
     * @param integer $nbMax
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getMosquesQuery($nbMax = null)
    {
        $qb = $this->createQueryBuilder("m");
        if (is_numeric($nbMax)) {
            $qb->setMaxResults($nbMax);
        }
        return $qb;
    }

    /**
     * get configured mosques with minimum one image set (image1)
     * @param integer $nbMax
     * @return array
     */
    function getMosquesWithImage($nbMax = null)
    {
        return $this->getMosquesQuery($nbMax)
            ->where("m.image1 IS NOT NULL")
            ->andWhere("m.type = 'mosque'")
            ->orderBy("m.id", "DESC")
            ->getQuery()
            ->getResult();
    }

    /**
     * set updated to now for all mosques
     */
    function forceUpdateAll()
    {
        $qb = $this->createQueryBuilder("m")
            ->update()
            ->set("m.updated", ":date")
            ->setParameter(":date", new \DateTime());
        $qb->getQuery()->execute();
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    function getCount()
    {
        return $this->createQueryBuilder("m")
            ->select("count(m.id)")
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    function countMosques()
    {
        return $this->createQueryBuilder("m")
            ->select("count(m.id)")
            ->where("m.type = 'mosque'")
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * get mosques information for google map
     * @return array
     */
    function getAllMosquesForMap()
    {
        return $this->createQueryBuilder("m")
            ->leftJoin("m.configuration", "c", "m.id = c.mosque_id")
            ->select("m.slug, m.name, m.address, m.city, m.zipcode, m.country,  c.longitude as lng, c.latitude as lat")
            ->where("m.addOnMap = 1")
            ->andWhere("m.type = 'mosque'")
            ->andWhere("m.status = :status")
            ->andWhere("c.latitude is not null")
            ->andWhere("c.longitude is not null")
            ->setParameter(':status', Mosque::STATUS_VALIDATED)
            ->getQuery()
            ->getArrayResult();
    }


    /**
     * get mosques by country
     * @return array
     */
    function getNumberByCountry()
    {
        return $this->createQueryBuilder("m")
            ->select("count(m.id) as nb, m.country")
            ->where("m.status = :status")
            ->orderBy("nb", "DESC")
            ->groupBy("m.country")
            ->getQuery()
            ->setParameter(':status', Mosque::STATUS_VALIDATED)
            ->getResult();
    }

    /**
     * @return array
     */
    function getCitiesByCountry($country)
    {
        $cities = $this->createQueryBuilder("m")
            ->select("UPPER(m.city) as city")
            ->distinct("m.city")
            ->where("m.country = :country")
            ->andWhere("m.type = :type")
            ->orderBy('m.city', 'ASC')
            ->setParameter(':country', $country)
            ->setParameter(':type', Mosque::TYPE_MOSQUE)
            ->getQuery()
            ->getScalarResult();

        return array_column($cities, 'city');
    }

    /**
     * Remove not validated mosques if no response after 15 days
     * @return integer
     */
    function removeNotValidated()
    {
        return $this->createQueryBuilder("m")
            ->delete()
            ->where("m.status != :newStatus")
            ->andWhere("m.updated < :date")
            ->setParameter(":newStatus", Mosque::STATUS_VALIDATED)
            ->setParameter(":date", new \DateTime("-15 day "))
            ->getQuery()
            ->execute();
    }
}
