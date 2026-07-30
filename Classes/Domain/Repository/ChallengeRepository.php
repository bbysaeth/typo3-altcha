<?php
declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\Domain\Repository;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ChallengeRepository extends Repository
{
    public const TABLE_NAME = 'tx_typo3altcha_domain_model_challenge';

    public function initializeObject(): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $querySettings->setRespectSysLanguage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Counts the challenges a cleanup run would remove.
     */
    public function countObsolete(int $expiresInSeconds, bool $includeSolved): int
    {
        $queryBuilder = $this->getConnectionQueryBuilder();
        $queryBuilder
            ->count('uid')
            ->from(self::TABLE_NAME)
            ->where($this->obsoleteConstraint($queryBuilder, $expiresInSeconds, $includeSolved));

        return (int)$queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * Removes obsolete challenges with a single statement and returns the number of
     * deleted rows.
     *
     * Mapping the rows to Extbase objects first would keep every one of them plus its
     * persistence session entry in memory. Installations that collect a lot of
     * challenges between two cleanup runs ran out of memory that way.
     */
    public function deleteObsolete(int $expiresInSeconds, bool $includeSolved): int
    {
        $queryBuilder = $this->getConnectionQueryBuilder();
        $queryBuilder
            ->delete(self::TABLE_NAME)
            ->where($this->obsoleteConstraint($queryBuilder, $expiresInSeconds, $includeSolved));

        return (int)$queryBuilder->executeStatement();
    }

    protected function getConnectionQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);
        // Hidden rows expire like any other one and would never be cleaned up
        // otherwise, because the default restrictions filter them out.
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder;
    }

    protected function obsoleteConstraint(
        QueryBuilder $queryBuilder,
        int $expiresInSeconds,
        bool $includeSolved
    ): CompositeExpression|string {
        $expired = $queryBuilder->expr()->lt(
            'tstamp',
            $queryBuilder->createNamedParameter(time() - $expiresInSeconds, Connection::PARAM_INT)
        );

        if (!$includeSolved) {
            return $expired;
        }

        return $queryBuilder->expr()->or(
            $expired,
            $queryBuilder->expr()->eq(
                'is_solved',
                $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)
            )
        );
    }
}
