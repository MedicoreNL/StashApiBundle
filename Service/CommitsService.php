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
     *
     * @var StashApiBundle\Service\BranchesService 
     */
    protected $branchService;


    /**
     * Constructor. 
     * 
     * @param Guzzle\Http\Client $client
     * @param StashApiBundle\Service\BranchesService $branchService
     */
    public function __construct(Client $client, BranchesService $branchService)
    {         
        $this->client = $client;
        $this->branchService = $branchService;
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
    public function getMergedBranchesFromBranch($baseBranch, $project, $repository)
    {
        $commits = null;
        $matchingBranches = $this->branchService->searchBranch($baseBranch, $project, $repository);
        if (count($matchingBranches) > 0) {
            $commits  = $this->getCommitsFromBranch($baseBranch, $project, $repository);        
        }
        $filteredCommits = array();
        
        if (null === $commits) {
            return null;
        }

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

    /**
     * Retrieve commits from a given branch name. And returns null when none found.
     *
     * @param string $branch
     * @param string $project
     * @param string $repository
     *
     * @return null|array
     */
    public function getCommitsFromBranch($branch, $project, $repository)
    {
        $url = $this->createUrl(
            $project,
            $repository,
            'commits',
            array(
                'until' => 'refs/heads/' . $branch
            )
        );        
        $data = $this->getResponseAsArray($url);
        if (false === isset($data['values'])) {
            return null;
        }

        return $data['values'];
    }   
}
