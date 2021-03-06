<?php
namespace Elastica\Test\Suggest;

use Elastica\Document;
use Elastica\Index;
use Elastica\Query;
use Elastica\Suggest\Completion;
use Elastica\Test\Base as BaseTest;

class CompletionTest extends BaseTest
{
    /**
     * @return Index
     */
    protected function _getIndexForTest()
    {
        $index = $this->_createIndex();
        $type = $index->getType('song');

        $type->setMapping([
            'fieldName' => [
                'type' => 'completion',
            ],
        ]);

        $type->addDocuments([
            new Document(1, [
                'fieldName' => [
                    'input' => ['Nevermind', 'Nirvana'],
                    'weight' => 5,
                ],
            ]),
            new Document(2, [
                'fieldName' => [
                    'input' => ['Bleach', 'Nirvana'],
                    'weight' => 2,
                ],
            ]),
            new Document(3, [
                'fieldName' => [
                    'input' => ['Incesticide', 'Nirvana'],
                    'weight' => 7,
                ],
            ]),
        ]);

        $index->refresh();

        return $index;
    }

    /**
     * @group unit
     */
    public function testToArray()
    {
        $suggest = new Completion('suggestName', 'fieldName');
        $suggest->setText('foo');
        $suggest->setSize(10);
        $expected = [
            'text' => 'foo',
            'completion' => [
                'size' => 10,
                'field' => 'fieldName',
            ],
        ];
        $this->assertEquals($expected, $suggest->toArray());
    }

    /**
     * @group functional
     */
    public function testSuggestWorks()
    {
        $suggest = new Completion('suggestName', 'fieldName');
        $suggest->setText('Never');

        $index = $this->_getIndexForTest();
        $resultSet = $index->search(Query::create($suggest));

        $this->assertTrue($resultSet->hasSuggests());

        $suggests = $resultSet->getSuggests();
        $options = $suggests['suggestName'][0]['options'];

        $this->assertCount(1, $options);
        $this->assertEquals('Nevermind', $options[0]['text']);
    }

    /**
     * @group functional
     */
    public function testFuzzySuggestWorks()
    {
        $suggest = new Completion('suggestName', 'fieldName');
        $suggest->setFuzzy(['fuzziness' => 2]);
        $suggest->setText('Neavermint');

        $index = $this->_getIndexForTest();
        $resultSet = $index->search(Query::create($suggest));

        $this->assertTrue($resultSet->hasSuggests());

        $suggests = $resultSet->getSuggests();
        $options = $suggests['suggestName'][0]['options'];

        $this->assertCount(1, $options);
        $this->assertEquals('Nevermind', $options[0]['text']);
    }

    /**
     * @group unit
     */
    public function testSetFuzzy()
    {
        $suggest = new Completion('suggestName', 'fieldName');

        $fuzzy = [
            'unicode_aware' => true,
            'fuzziness' => 3,
        ];

        $suggest->setFuzzy($fuzzy);

        $this->assertEquals($fuzzy, $suggest->getParam('fuzzy'));

        $this->assertInstanceOf('Elastica\\Suggest\\Completion', $suggest->setFuzzy($fuzzy));
    }
}
