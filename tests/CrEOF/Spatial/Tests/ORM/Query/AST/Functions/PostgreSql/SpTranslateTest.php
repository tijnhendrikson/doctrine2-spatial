<?php
/**
 * Copyright (C) 2020 Alexandre Tranchant
 * Copyright (C) 2015 Derek J. Lambert
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace CrEOF\Spatial\Tests\ORM\Query\AST\Functions\PostgreSql;

use CrEOF\Spatial\Exception\InvalidValueException;
use CrEOF\Spatial\Exception\UnsupportedPlatformException;
use CrEOF\Spatial\Tests\Helper\PolygonHelperTrait;
use CrEOF\Spatial\Tests\OrmTestCase;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;

/**
 * SP_Translate DQL function tests.
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @translateDefaultClass
 * @coversNothing
 */
class SpTranslateTest extends OrmTestCase
{
    use PolygonHelperTrait;

    /**
     * Setup the function type test.
     *
     * @throws DBALException                when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->supportsPlatform('postgresql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the predicate.
     *
     * @throws DBALException                when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     * @throws InvalidValueException        when geometries are not valid
     *
     * @group geometry
     */
    public function testFunctionInPredicate()
    {
        $bigPolygon = $this->createBigPolygon();
        $this->createSmallPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p FROM CrEOF\Spatial\Tests\Fixtures\PolygonEntity p WHERE PgSql_Translate(p.polygon, :x, :y) = :g'
            // phpcs:enable
        );
        $query->setParameter('g', 'POLYGON((4 -4.5,14 -4.5,14 5.5,4 5.5,4 -4.5))', 'string');
        $query->setParameter('x', 4.0);
        $query->setParameter('y', -4.5);
        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($bigPolygon, $result[0]);
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @throws DBALException                when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     * @throws InvalidValueException        when geometries are not valid
     *
     * @group geometry
     */
    public function testFunctionInSelect()
    {
        $bigPolygon = $this->createBigPolygon();
        $smallPolygon = $this->createSmallPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p, ST_AsText(PgSql_Translate(p.polygon, :x, :y)) FROM CrEOF\Spatial\Tests\Fixtures\PolygonEntity p'
            // phpccs: enable
        );
        $query->setParameter('x', 4.0);
        $query->setParameter('y', -4.5);
        $result = $query->getResult();

        static::assertCount(2, $result);
        static::assertEquals($bigPolygon, $result[0][0]);
        static::assertSame('POLYGON((4 -4.5,14 -4.5,14 5.5,4 5.5,4 -4.5))', $result[0][1]);
        static::assertEquals($smallPolygon, $result[1][0]);
        static::assertSame('POLYGON((9 0.5,11 0.5,11 2.5,9 2.5,9 0.5))', $result[1][1]);
    }
}
