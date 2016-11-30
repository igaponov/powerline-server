<?php

namespace Civix\ApiBundle\Controller;

use Civix\CoreBundle\Entity\CiceroRepresentative;
use Civix\CoreBundle\Entity\Representative;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/representatives")
 */
class RepresentativeController extends BaseController
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * @Route("/", name="api_my_representatives")
     * @Method("GET")
     *
     * @ApiDoc(
     *     resource=true,
     *     section="Representatives",
     *     description="Get list of representatives by district",
     *     statusCodes={
     *         200="Returns list representatives",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function getMyRepresentativesAction()
    {
        $districts = $this->getUser()->getDistrictsIds();

        $nonLegislativeRepresentatives = $this->em->getRepository(Representative::class)
            ->getNonLegislativeRepresentative($districts);
        $representatives = $this->em->getRepository(CiceroRepresentative::class)
            ->getByDistricts($districts);

        $representativesByDistrict = array();

        if ($nonLegislativeRepresentatives) {
            $nonLegislativeRepresentatives = array_map(function ($representativeInfo) {
                return array('representative' => $representativeInfo);
            }, $nonLegislativeRepresentatives);
            $representativesByDistrict['Local'] = array(
                'title' => 'Local',
                'representatives' => $nonLegislativeRepresentatives,
            );
        }

        foreach ($representatives as $singleRepresentative) {
            if (empty($representativesByDistrict[$singleRepresentative->getDistrictTypeName()])) {
                $representativesByDistrict[$singleRepresentative->getDistrictTypeName()] = array(
                    'title' => $singleRepresentative->getDistrictTypeName(),
                    'representatives' => array(),
                );
            }
            $representativesByDistrict[$singleRepresentative->getDistrictTypeName()]['representatives'][] = $singleRepresentative;
        }

        $response = new Response($this->jmsSerialization(array_values($representativesByDistrict),
            array('api-representatives-list'))
        );
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route(
     *  "/info/{representative_id}/{storage_id}",
     *   name="api_representative_information",
     *   requirements={
     *      "representative_id" = "\d+",
     *      "storage_id" = "\d+"
     *   }
     * )
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Representatives",
     *     description="Get information on representative",
     *     statusCodes={
     *         200="Get information on representative",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function getInformationAction(Request $request)
    {
        $info = $this->em->getRepository('CivixCoreBundle:Representative')
                ->getRepresentativeInformation($request->get('representative_id'), $request->get('storage_id'));

        if (!($info)) {
            throw $this->createNotFoundException();
        }

        $response = new Response($this->jmsSerialization($info, array('api-info')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route(
     *      "/info/committee/{storage_id}",
     *      name="api_representative_committee",
     *      requirements={
     *          "storage_id" = "\d+"
     *      }
     * )
     * @ParamConverter(
     *      "representative",
     *      class="CivixCoreBundle:CiceroRepresentative",
     *      options={"mapping" = {"storage_id" = "id"}}
     * )
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Representatives",
     *     description="Get committee membership of representative",
     *     statusCodes={
     *         200="Get committee membership of representative",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param CiceroRepresentative $representative
     * @return Response
     */
    public function getCommitteeInfo(CiceroRepresentative $representative)
    {
        $responseBody = array();
        $openStateId = $representative->getOpenstateId();
        if ($openStateId) {
            $responseBody = $this->get('civix_core.openstates_api')
                ->getCommiteeMembership($openStateId);
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($this->jmsSerialization($responseBody, 'api-committee'));

        return $response;
    }

    /**
     * @Route(
     *      "/info/sponsored-bills/{storage_id}",
     *      name="api_representative_sponsored_bills",
     *      requirements={
     *          "storage_id" = "\d+"
     *      }
     * )
     * @ParamConverter(
     *      "representative",
     *      class="CivixCoreBundle:CiceroRepresentative",
     *      options={"mapping" = {"storage_id" = "id"}}
     * )
     * @Method("GET")
     *
     * @ApiDoc(
     *     section="Representatives",
     *     description="Get sponsored bills by representative",
     *     statusCodes={
     *         200="Get sponsored bills by representative",
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param CiceroRepresentative $representative
     * @return Response
     */
    public function getSponsoredBills(CiceroRepresentative $representative)
    {
        $responseBody = array();
        $openStateId = $representative->getOpenstateId();
        if ($openStateId) {
            $responseBody = $this->get('civix_core.openstates_api')
                ->getBillsBySponsorId($openStateId);
        }

        $response = new Response();
        $response->setContent($this->jmsSerialization($responseBody, 'api-bills'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
