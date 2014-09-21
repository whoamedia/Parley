<?php namespace SRLabs\Parley;

use Illuminate\Support\Collection;
use ReflectionClass;
use SRLabs\Parley\Models\Thread;

class ParleySelector {

    protected $level;

    protected $trashed;

    protected $members;

    public function __construct($options = null)
    {
        if ($options && is_array($options)) {
            $this->level   = ( array_key_exists('level', $options) ? $options['level'] : 'all' );
            $this->trashed = ( array_key_exists('trashed', $options) ? $options['trashed'] : 'no' );
        }
    }

    /**
     *
     * @return $this
     */
    public function withTrashed()
    {
        $this->trashed = 'yes';

        return $this;
    }

    public function onlyTrashed()
    {
        $this->trashed = 'only';

        return $this;
    }

    /**
     * @param $members
     *
     * @return $this
     */
    public function belongingTo($members)
    {
        if ( ! is_array($members) )
        {
            $members = [$members];
        }

        $this->members = $members;

        return $this;
    }

    /**
     *
     * @return Collection
     */
    public function get()
    {
        $results = new Collection();

        foreach ($this->members as $member)
        {
            $results = $results->merge($this->getThreads($member));
        }

        return $results;
    }

    /**
     * @param $member
     *
     * @return mixed
     */
    public function getThreads( $member )
    {
        $query = Thread::join('parley_members', 'parley_threads.id', '=', 'parley_members.parley_thread_id')
            ->where('parley_members.parleyable_id', $member->id)
            ->where('parley_members.parleyable_type', $this->getObjectClassName($member));

        switch ($this->trashed)
        {
            case 'yes':
                $query = $query->withTrashed();
                break;

            case 'only':
                $query = $query->onlyTrashed();
                break;

            default:
                break;
        }

        switch ($this->level)
        {
            case 'open':
                $query = $query->whereNull('parley_threads.closed_at');
                break;

            case 'closed':
                $query = $query->whereNotNull('parley_threads.closed_at');
                break;

            default:
                break;
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }

    /**
     * @param $object
     *
     * @return mixed
     */
    protected function getObjectClassName( $object )
    {
        // Reflect on the Object
        $reflector = new ReflectionClass( $object );

        // Return the class name
        return $reflector->getName();
    }

} 