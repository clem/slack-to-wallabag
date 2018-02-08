<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use App\Services\Utils\ArrayUtils;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @throws
     */
    public function index() : Response
    {
        // Initialize
        $displayedDays = (int) $this->container->getParameter('app.home.displayed_days');
        $em = $this->getDoctrine()->getManager();
        $linkRepository = $em->getRepository('App:SlackLink');
        $userRepository = $em->getRepository('App:SlackUser');

        // Get links info
        $totalLinks = $linkRepository->countAll();
        $getCreatedLinks = $linkRepository->countByDate('createdAt', $displayedDays);
        $getExportedLinks = $linkRepository->countByDate('exportedAt', $displayedDays);

        // Format links info
        $getCreatedLinks = ArrayUtils::formatTotalArray($getCreatedLinks);
        $getExportedLinks = ArrayUtils::formatTotalArray($getExportedLinks);
        $createdLinks = [];
        $exportedLinks = [];
        for ($i = ($displayedDays - 1); $i === 0; $i--) {
            // Initialize
            $dayDate = date('Y-m-d', strtotime((-1 * $i).' days'));

            // Add date to links arrays
            $createdLinks[$dayDate] = array_key_exists($dayDate, $getCreatedLinks) ? $getCreatedLinks[$dayDate] : 0;
            $exportedLinks[$dayDate] = array_key_exists($dayDate, $getExportedLinks) ? $getExportedLinks[$dayDate] : 0;
        }

        // Get channels info
        $channelsInfo = $linkRepository->countByChannel();

        // Get user info
        $totalUsers = $userRepository->countAll();

        // Render homepage
        return $this->render('homepage.html.twig', [
            'displayed_days' => $displayedDays,
            'total_links' => $totalLinks,
            'channels_info' => $channelsInfo,
            'total_users' => $totalUsers,
            'created_links' => $createdLinks,
            'exported_links' => $exportedLinks,
        ]);
    }
}
