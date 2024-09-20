import React from 'react'
import { motion } from "framer-motion/dist/framer-motion"

const animations = {
    initial: { opacity: 0, },
    animate: { opacity: 1 },
    exit: { opacity: 0, },
}

const AnimatedPage = ({ children }) => {
    return (
        <motion.div
            variants={animations}
            className="animatedPage"
            initial="initial"
            animate="animate"
            exit="exit "
            transition={{ duration: 1.3 }}>
            {children}
        </motion.div>
    )
}

export default AnimatedPage;
