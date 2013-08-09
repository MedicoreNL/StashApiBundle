<?php

namespace StashApiBundle\Service;

use Guzzle\Http\Client;
use StashApiBundle\Service\BranchesService;

/**
 * Service class that deals with 'commits' related stash apis. 
 */
class CommitsService extends AbstractService
{
    /**
     * Constructor. 
     * 
     * @param Guzzle\Http\Client $client
     */
    public function __construct(Client $client)
    {         
        $this->client = $client;
    }
    
    /**
     * Get a list of merged branches as an array based on 
     * Git normal merge commits.
     *
     * This means, if the commit message is heavily modified this method will
     * not include it into the list.
     *
     * @param string $baseBranch
     * @param string $project
     * @param string $repository
     *
     * @return null|array
     */
    public function getMergedBranches($project, $repository, $baseBranch)
    {
        $params = array(
            'until' => sprintf('refs/heads/%s', $baseBranch)
        );

        $commits  = $this->getCommits($project, $repository, $params);
        if (null === $commits) {
            return null;
        }

        return $this->filterMergeCommits($commits);
    }

    /**
     * Retrieve commits from a given branch name. And returns null when none found.
     *
     * @param string $project
     * @param string $repository
     * @param array $params
     *
     * @return null|array
     */
    public function getCommits($project, $repository, $params = array())
    {
        $url = $this->createUrl(
            $project,
            $repository,
            'commits',
            $params
        );
        $data = $this->getResponseAsArray($url);

        if (false === isset($data['values'])) {
            return null;
        }

        return $data['values'];
    }

    /**
     * Method to filter merge commits from given array of commits.
     *
     * @param array $commits
     *
     * @return null|array
     */
    private function filterMergeCommits(array $commits)
    {
        $filteredCommits = array();

        foreach ($commits as $commit) {
            if (preg_match('/^Merge .* from (.*) to .*/', $commit['message'], $matches)) {
                $filteredCommits[] = $matches[1];
            }
        }

        if (count($filteredCommits) == 0) {
            return null;
        }

        return $filteredCommits;
    }
}
